<?php

namespace App\Service;

use App\Entity\Image;
use App\Entity\PrintOrder;
use App\Entity\PrintOrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PrintOrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProdigiApiService $prodigiApiService,
        private CartService $cartService,
        private string $projectDir
    ) {}

    /**
     * Créer une commande de tirage à partir du panier
     */
    public function createOrderFromCart(User $customer, array $shippingAddress): PrintOrder
    {
        $cart = $this->cartService->getCart();
        $printItems = array_filter($cart, fn($item) => $item['item_type'] === 'print_order');

        if (empty($printItems)) {
            throw new \Exception('Aucun tirage dans le panier');
        }

        $order = new PrintOrder();
        $order->setCustomer($customer);
        $order->setShippingAddress($shippingAddress);

        $totalAmount = 0.0;

        foreach ($printItems as $cartItem) {
            $metadata = $cartItem['metadata'];
            $image = $this->entityManager->getRepository(Image::class)->find($metadata['image_id']);
            
            if (!$image) {
                continue;
            }

            $orderItem = new PrintOrderItem();
            $orderItem->setImage($image)
                     ->setPrintFormat($metadata['format'])
                     ->setPaperType($metadata['paper_type'])
                     ->setQuantity($cartItem['quantity'])
                     ->setUnitPrice($cartItem['unit_price']);

            $order->addItem($orderItem);
            $totalAmount += floatval($orderItem->getTotalPrice());
        }

        $order->setTotalAmount(number_format($totalAmount, 2, '.', ''));

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Envoyer la commande à Prodigi
     */
    public function sendOrderToProdigi(PrintOrder $order): bool
    {
        try {
            // Préparer les données pour Prodigi
            $orderItems = [];
            foreach ($order->getItems() as $item) {
                $orderItems[] = [
                    'image_id' => $this->uploadImageToProdigi($item->getImage()),
                    'format' => $item->getPrintFormat(),
                    'paper_type' => $item->getPaperType(),
                    'quantity' => $item->getQuantity()
                ];
            }

            $prodigiOrderData = $this->prodigiApiService->formatOrderData(
                $orderItems,
                $order->getShippingAddress(),
                $order->getCustomer()->getEmail()
            );

            // Créer la commande chez Prodigi
            $prodigiResponse = $this->prodigiApiService->createOrder($prodigiOrderData);

            // Mettre à jour notre commande avec les données Prodigi
            $order->setProdigiOrderId($prodigiResponse['order_id'] ?? null)
                  ->setProdigiOrderData($prodigiResponse)
                  ->setStatus('sent_to_prodigi');

            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            // Log l'erreur et marquer la commande comme échouée
            error_log('Erreur envoi commande Prodigi: ' . $e->getMessage());
            $order->setStatus('error');
            $this->entityManager->flush();
            
            return false;
        }
    }

    /**
     * Ajouter un tirage au panier
     */
    public function addPrintToCart(Image $image, string $format, string $paperType, int $quantity = 1): void
    {
        // Calculer le prix avec marge
        $unitPrice = $this->calculatePrintPriceWithMargin($format, $paperType);

        $this->cartService->addItem(
            'print_order',
            $image->getId(),
            sprintf('%s - %s (%s)', $image->getOriginalName(), $format, $paperType),
            number_format($unitPrice, 2, '.', ''),
            $quantity,
            [
                'image_id' => $image->getId(),
                'format' => $format,
                'paper_type' => $paperType,
                'gallery_id' => $image->getGallery()->getId()
            ]
        );
    }

    /**
     * Obtenir les commandes d'un client
     */
    public function getCustomerOrders(User $customer): array
    {
        return $this->entityManager->getRepository(PrintOrder::class)
            ->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    /**
     * Mettre à jour le statut d'une commande depuis Prodigi
     */
    public function updateOrderStatusFromProdigi(PrintOrder $order): void
    {
        if (!$order->getProdigiOrderId()) {
            return;
        }

        try {
            $prodigiStatus = $this->prodigiApiService->getOrderStatus($order->getProdigiOrderId());
            
            // Mapper les statuts Prodigi vers nos statuts
            $statusMapping = [
                'received' => 'confirmed',
                'processing' => 'processing',
                'shipped' => 'shipped',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled'
            ];

            $newStatus = $statusMapping[$prodigiStatus['status']] ?? $order->getStatus();
            
            if ($newStatus !== $order->getStatus()) {
                $order->setStatus($newStatus);
                
                if ($newStatus === 'shipped' && isset($prodigiStatus['shipped_at'])) {
                    $order->setShippedAt(new \DateTimeImmutable($prodigiStatus['shipped_at']));
                }
                
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            error_log('Erreur mise à jour statut commande: ' . $e->getMessage());
        }
    }

    private function uploadImageToProdigi(Image $image): string
    {
        // En production, uploader l'image vers Prodigi et retourner l'ID Prodigi
        // Pour l'instant, retourner l'ID local
        return (string) $image->getId();
    }

    /**
     * Calculer le prix d'un tirage (sans marge, prix Prodigi)
     */
    public function calculatePrintPrice(string $format, string $paperType, int $quantity = 1): float
    {
        $formats = $this->prodigiApiService->getAvailableFormats();
        $paperTypes = $this->prodigiApiService->getAvailablePaperTypes();
        
        $basePrice = $formats[$format]['base_price'] ?? 0.0;
        $multiplier = $paperTypes[$paperType]['price_multiplier'] ?? 1.0;
        
        return $basePrice * $multiplier * $quantity;
    }

    /**
     * Calculer le prix d'un tirage avec marge (prix client)
     */
    public function calculatePrintPriceWithMargin(string $format, string $paperType, int $quantity = 1): float
    {
        $prodigiPrice = $this->calculatePrintPrice($format, $paperType, 1);
        $customMargins = $this->getCustomMargins();
        
        // Chercher une marge spécifique pour ce format/papier
        $marginKey = $format . '_' . $paperType;
        $margin = $customMargins[$marginKey] ?? $customMargins['default'] ?? 50; // 50% par défaut
        
        // Appliquer la marge
        $customerPrice = $prodigiPrice * (1 + ($margin / 100));
        
        return $customerPrice * $quantity;
    }

    /**
     * Obtenir les marges personnalisées
     */
    private function getCustomMargins(): array
    {
        $configFile = $this->projectDir . '/config/print_margins.json';
        
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            return json_decode($content, true) ?: [];
        }

        // Marges par défaut
        return [
            'default' => 50, // 50% de marge par défaut
        ];
    }
}