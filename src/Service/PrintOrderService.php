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
        private CeweApiService $ceweApiService,
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
     * Envoyer la commande à CEWE
     */
    public function sendOrderToCewe(PrintOrder $order): bool
    {
        try {
            // Préparer les données pour CEWE
            $orderItems = [];
            foreach ($order->getItems() as $item) {
                $orderItems[] = [
                    'image_id' => $this->uploadImageToCewe($item->getImage()),
                    'format' => $item->getPrintFormat(),
                    'paper_type' => $item->getPaperType(),
                    'quantity' => $item->getQuantity()
                ];
            }

            $ceweOrderData = $this->ceweApiService->formatOrderData(
                $orderItems,
                $order->getShippingAddress(),
                $order->getCustomer()->getEmail()
            );

            // Créer la commande chez CEWE
            $ceweResponse = $this->ceweApiService->createOrder($ceweOrderData);

            // Mettre à jour notre commande avec les données CEWE
            $order->setCeweOrderId($ceweResponse['order_id'] ?? null)
                  ->setCeweOrderData($ceweResponse)
                  ->setStatus('sent_to_cewe');

            $this->entityManager->flush();

            return true;
        } catch (\Exception $e) {
            // Log l'erreur et marquer la commande comme échouée
            error_log('Erreur envoi commande CEWE: ' . $e->getMessage());
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
     * Mettre à jour le statut d'une commande depuis CEWE
     */
    public function updateOrderStatusFromCewe(PrintOrder $order): void
    {
        if (!$order->getCeweOrderId()) {
            return;
        }

        try {
            $ceweStatus = $this->ceweApiService->getOrderStatus($order->getCeweOrderId());
            
            // Mapper les statuts CEWE vers nos statuts
            $statusMapping = [
                'received' => 'confirmed',
                'processing' => 'processing',
                'shipped' => 'shipped',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled'
            ];

            $newStatus = $statusMapping[$ceweStatus['status']] ?? $order->getStatus();
            
            if ($newStatus !== $order->getStatus()) {
                $order->setStatus($newStatus);
                
                if ($newStatus === 'shipped' && isset($ceweStatus['shipped_at'])) {
                    $order->setShippedAt(new \DateTimeImmutable($ceweStatus['shipped_at']));
                }
                
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            error_log('Erreur mise à jour statut commande: ' . $e->getMessage());
        }
    }

    private function uploadImageToCewe(Image $image): string
    {
        // En production, uploader l'image vers CEWE et retourner l'ID CEWE
        // Pour l'instant, retourner l'ID local
        return (string) $image->getId();
    }

    /**
     * Calculer le prix d'un tirage (sans marge, prix CEWE)
     */
    public function calculatePrintPrice(string $format, string $paperType, int $quantity = 1): float
    {
        $formats = $this->ceweApiService->getAvailableFormats();
        $paperTypes = $this->ceweApiService->getAvailablePaperTypes();
        
        $basePrice = $formats[$format]['base_price'] ?? 0.0;
        $multiplier = $paperTypes[$paperType]['price_multiplier'] ?? 1.0;
        
        return $basePrice * $multiplier * $quantity;
    }

    /**
     * Calculer le prix d'un tirage avec marge (prix client)
     */
    public function calculatePrintPriceWithMargin(string $format, string $paperType, int $quantity = 1): float
    {
        $cewePrice = $this->calculatePrintPrice($format, $paperType, 1);
        $customMargins = $this->getCustomMargins();
        
        // Chercher une marge spécifique pour ce format/papier
        $marginKey = $format . '_' . $paperType;
        $margin = $customMargins[$marginKey] ?? $customMargins['default'] ?? 50; // 50% par défaut
        
        // Appliquer la marge
        $customerPrice = $cewePrice * (1 + ($margin / 100));
        
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