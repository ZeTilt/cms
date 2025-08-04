<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\CartService;
use App\Service\MangoPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $entityManager,
        private MangoPayService $mangoPayService
    ) {}

    #[Route('', name: 'cart_index')]
    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        $totalAmount = $this->cartService->getTotalAmount();

        return $this->render('cart/index.html.twig', [
            'cart_items' => $cart,
            'total_amount' => $totalAmount,
            'item_count' => $this->cartService->getItemCount()
        ]);
    }

    #[Route('/add/{type}/{id}', name: 'cart_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addToCart(string $type, int $id, Request $request): JsonResponse
    {
        try {
            $quantity = (int) $request->request->get('quantity', 1);
            
            switch ($type) {
                case 'gallery':
                    $gallery = $this->entityManager->getRepository(Gallery::class)->find($id);
                    if (!$gallery) {
                        return new JsonResponse(['error' => 'Galerie non trouvée'], 404);
                    }
                    
                    // Vérifier si l'utilisateur a accès à cette galerie
                    if ($gallery->getVisibility() === 'private' && !$gallery->isAccessibleByUser($this->getUser())) {
                        return new JsonResponse(['error' => 'Accès non autorisé à cette galerie'], 403);
                    }
                    
                    $this->cartService->addGallery($gallery);
                    break;
                    
                default:
                    return new JsonResponse(['error' => 'Type d\'élément non supporté'], 400);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Élément ajouté au panier',
                'cart_count' => $this->cartService->getItemCount(),
                'cart_total' => $this->cartService->getTotalAmount()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'ajout au panier'], 500);
        }
    }

    #[Route('/remove/{type}/{id}', name: 'cart_remove', methods: ['POST'])]
    public function removeFromCart(string $type, int $id): JsonResponse
    {
        $this->cartService->removeItem($type . '_access', $id);

        return new JsonResponse([
            'success' => true,
            'message' => 'Élément supprimé du panier',
            'cart_count' => $this->cartService->getItemCount(),
            'cart_total' => $this->cartService->getTotalAmount()
        ]);
    }

    #[Route('/update/{type}/{id}', name: 'cart_update', methods: ['POST'])]
    public function updateQuantity(string $type, int $id, Request $request): JsonResponse
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $this->cartService->updateQuantity($type . '_access', $id, $quantity);

        return new JsonResponse([
            'success' => true,
            'cart_count' => $this->cartService->getItemCount(),
            'cart_total' => $this->cartService->getTotalAmount()
        ]);
    }

    #[Route('/clear', name: 'cart_clear', methods: ['POST'])]
    public function clearCart(): JsonResponse
    {
        $this->cartService->clearCart();

        return new JsonResponse([
            'success' => true,
            'message' => 'Panier vidé'
        ]);
    }

    #[Route('/checkout', name: 'cart_checkout')]
    #[IsGranted('ROLE_USER')]
    public function checkout(): Response
    {
        // Valider le panier
        $errors = $this->cartService->validateCart();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('cart_index');
        }

        $cart = $this->cartService->getCart();
        $totalAmount = $this->cartService->getTotalAmount();

        return $this->render('cart/checkout.html.twig', [
            'cart_items' => $cart,
            'total_amount' => $totalAmount,
            'user' => $this->getUser()
        ]);
    }

    #[Route('/process-payment', name: 'cart_process_payment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function processPayment(Request $request): Response
    {
        try {
            // Valider le panier
            $errors = $this->cartService->validateCart();
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('cart_checkout');
            }

            $cart = $this->cartService->getCart();
            $totalAmount = $this->cartService->getTotalAmount();

            // Créer la commande
            $order = new Order();
            $order->setOrderNumber('ORD-' . strtoupper(uniqid()));
            $order->setCustomer($this->getUser());
            $order->setTotalAmount($totalAmount);
            $order->setStatus('pending');
            $order->setCurrency('EUR');

            // Ajouter les éléments de commande
            foreach ($cart as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setItemType($cartItem['item_type']);
                $orderItem->setItemId($cartItem['item_id']);
                $orderItem->setItemName($cartItem['item_name']);
                $orderItem->setQuantity($cartItem['quantity']);
                $orderItem->setUnitPrice($cartItem['unit_price']);
                $orderItem->setTotalPrice($cartItem['total_price']);
                
                if (isset($cartItem['metadata'])) {
                    $orderItem->setMetadata($cartItem['metadata']);
                }

                $order->addOrderItem($orderItem);
                $this->entityManager->persist($orderItem);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Si le montant est 0 (accès gratuit), marquer comme payé
            if (bccomp($totalAmount, '0.00', 2) === 0) {
                $order->setStatus('paid');
                $this->entityManager->flush();
                
                // Vider le panier
                $this->cartService->clearCart();
                
                $this->addFlash('success', 'Votre commande a été traitée avec succès.');
                return $this->redirectToRoute('cart_order_confirmation', ['orderNumber' => $order->getOrderNumber()]);
            }

            // Traitement du paiement avec MangoPay pour les montants > 0
            // TODO: Intégrer le processus de paiement MangoPay
            
            $this->addFlash('info', 'Redirection vers le processus de paiement...');
            return $this->redirectToRoute('cart_payment', ['orderNumber' => $order->getOrderNumber()]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du traitement de la commande: ' . $e->getMessage());
            return $this->redirectToRoute('cart_checkout');
        }
    }

    #[Route('/payment/{orderNumber}', name: 'cart_payment')]
    #[IsGranted('ROLE_USER')]
    public function payment(string $orderNumber): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderNumber]);
        
        if (!$order || $order->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('cart/payment.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/confirmation/{orderNumber}', name: 'cart_order_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function orderConfirmation(string $orderNumber): Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['orderNumber' => $orderNumber]);
        
        if (!$order || $order->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande non trouvée');
        }

        return $this->render('cart/confirmation.html.twig', [
            'order' => $order
        ]);
    }
}