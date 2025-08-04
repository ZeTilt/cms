<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Payment;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/shop')]
#[IsGranted('ROLE_ADMIN')]
class ShopController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {}

    #[Route('', name: 'admin_shop_dashboard')]
    public function dashboard(): Response
    {
        // Vérifier que le module shop est activé
        if (!$this->moduleManager->isModuleActive('shop')) {
            throw $this->createNotFoundException('Le module Shop n\'est pas activé.');
        }

        // Statistiques des commandes
        $orderRepository = $this->entityManager->getRepository(Order::class);
        $paymentRepository = $this->entityManager->getRepository(Payment::class);

        $totalOrders = $orderRepository->count([]);
        $pendingOrders = $orderRepository->count(['status' => 'pending']);
        $paidOrders = $orderRepository->count(['status' => 'paid']);
        
        // Revenus du mois en cours
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');
        
        $monthlyRevenue = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->andWhere('o.createdAt BETWEEN :start AND :end')
            ->setParameter('status', 'paid')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Commandes récentes
        $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/shop/dashboard.html.twig', [
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'paid_orders' => $paidOrders,
            'monthly_revenue' => $monthlyRevenue,
            'recent_orders' => $recentOrders,
        ]);
    }

    #[Route('/orders', name: 'admin_shop_orders')]
    public function orders(): Response
    {
        if (!$this->moduleManager->isModuleActive('shop')) {
            throw $this->createNotFoundException('Le module Shop n\'est pas activé.');
        }

        $orderRepository = $this->entityManager->getRepository(Order::class);
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/shop/orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/order/{id}', name: 'admin_shop_order_detail')]
    public function orderDetail(Order $order): Response
    {
        if (!$this->moduleManager->isModuleActive('shop')) {
            throw $this->createNotFoundException('Le module Shop n\'est pas activé.');
        }

        return $this->render('admin/shop/order_detail.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/payments', name: 'admin_shop_payments')]
    public function payments(): Response
    {
        if (!$this->moduleManager->isModuleActive('shop')) {
            throw $this->createNotFoundException('Le module Shop n\'est pas activé.');
        }

        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $payments = $paymentRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/shop/payments.html.twig', [
            'payments' => $payments,
        ]);
    }

    #[Route('/settings', name: 'admin_shop_settings')]
    public function settings(): Response
    {
        if (!$this->moduleManager->isModuleActive('shop')) {
            throw $this->createNotFoundException('Le module Shop n\'est pas activé.');
        }

        $shopConfig = $this->moduleManager->getModuleConfig('shop');

        return $this->render('admin/shop/settings.html.twig', [
            'config' => $shopConfig,
        ]);
    }
}