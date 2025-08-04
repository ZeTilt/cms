<?php

namespace App\Controller;

use App\Entity\Image;
use App\Service\PrintOrderService;
use App\Service\CartService;
use App\Service\CeweApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PrintOrderController extends AbstractController
{
    public function __construct(
        private PrintOrderService $printOrderService,
        private CartService $cartService,
        private CeweApiService $ceweApiService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/print/add-to-cart', name: 'add_print_to_cart', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addPrintToCart(Request $request): JsonResponse
    {
        try {
            $imageId = $request->request->get('image_id');
            $format = $request->request->get('format');
            $paperType = $request->request->get('paper_type');
            $quantity = (int) $request->request->get('quantity', 1);

            if (!$imageId || !$format || !$paperType) {
                return new JsonResponse(['success' => false, 'error' => 'Paramètres manquants']);
            }

            $image = $this->entityManager->getRepository(Image::class)->find($imageId);
            if (!$image) {
                return new JsonResponse(['success' => false, 'error' => 'Image non trouvée']);
            }

            $this->printOrderService->addPrintToCart($image, $format, $paperType, $quantity);

            return new JsonResponse([
                'success' => true,
                'message' => 'Tirage ajouté au panier',
                'cart_count' => $this->cartService->getItemCount()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/print/calculate-price', name: 'calculate_print_price', methods: ['POST'])]
    public function calculatePrintPrice(Request $request): JsonResponse
    {
        try {
            $format = $request->request->get('format');
            $paperType = $request->request->get('paper_type');
            $quantity = (int) $request->request->get('quantity', 1);

            if (!$format || !$paperType) {
                return new JsonResponse(['success' => false, 'error' => 'Paramètres manquants']);
            }

            $price = $this->printOrderService->calculatePrintPrice($format, $paperType, $quantity);

            return new JsonResponse([
                'success' => true,
                'price' => number_format($price, 2, '.', '')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/cart/contents', name: 'cart_contents', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getCartContents(): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart();
            $total = $this->cartService->getTotalAmount();

            return new JsonResponse([
                'success' => true,
                'items' => array_values($cart),
                'total' => $total,
                'count' => $this->cartService->getItemCount()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/cart/count', name: 'cart_count', methods: ['GET'])]
    public function getCartCount(): JsonResponse
    {
        try {
            return new JsonResponse([
                'success' => true,
                'count' => $this->cartService->getItemCount()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'count' => 0
            ]);
        }
    }

    #[Route('/cart/remove', name: 'cart_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function removeFromCart(Request $request): JsonResponse
    {
        try {
            $itemType = $request->request->get('item_type');
            $itemId = (int) $request->request->get('item_id');

            if (!$itemType || !$itemId) {
                return new JsonResponse(['success' => false, 'error' => 'Paramètres manquants']);
            }

            $this->cartService->removeItem($itemType, $itemId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Article supprimé du panier',
                'cart_count' => $this->cartService->getItemCount()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/cart/checkout', name: 'cart_checkout', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(): Response
    {
        $cart = $this->cartService->getCart();
        $printItems = array_filter($cart, fn($item) => $item['item_type'] === 'print_order');

        if (empty($printItems)) {
            $this->addFlash('error', 'Votre panier de tirages est vide.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'total' => $this->cartService->getTotalAmount(),
            'item_count' => $this->cartService->getItemCount()
        ]);
    }

    #[Route('/admin/print-orders', name: 'admin_print_orders', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('admin/print_orders/index.html.twig', [
            'orders' => []
        ]);
    }
}