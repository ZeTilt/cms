<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Service;
use App\Entity\Event;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bookings')]
#[IsGranted('ROLE_ADMIN')]
class BookingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'admin_bookings_list')]
    public function list(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->entityManager->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.user', 'u')
            ->leftJoin('b.service', 's')
            ->leftJoin('b.event', 'e')
            ->addSelect('u', 's', 'e')
            ->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        // Filter by status
        $status = $request->query->get('status');
        if ($status) {
            $queryBuilder->andWhere('b.status = :status')
                ->setParameter('status', $status);
        }

        // Filter by payment status
        $paymentStatus = $request->query->get('payment_status');
        if ($paymentStatus) {
            $queryBuilder->andWhere('b.paymentStatus = :paymentStatus')
                ->setParameter('paymentStatus', $paymentStatus);
        }

        // Filter by type (service or event)
        $type = $request->query->get('type');
        if ($type === 'service') {
            $queryBuilder->andWhere('b.service IS NOT NULL');
        } elseif ($type === 'event') {
            $queryBuilder->andWhere('b.event IS NOT NULL');
        }

        $bookings = $queryBuilder->getQuery()->getResult();

        // Count total for pagination
        $totalBookings = $this->entityManager->getRepository(Booking::class)
            ->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($totalBookings / $limit);

        // Get stats for dashboard
        $stats = $this->getBookingStats();

        return $this->render('admin/bookings/list.html.twig', [
            'bookings' => $bookings,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalBookings' => $totalBookings,
            'currentStatus' => $status,
            'currentPaymentStatus' => $paymentStatus,
            'currentType' => $type,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'admin_bookings_new')]
    public function new(): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        $services = $this->entityManager->getRepository(Service::class)
            ->findBy(['status' => 'active', 'isBookable' => true]);
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.requiresRegistration = :requiresRegistration')
            ->setParameter('status', 'published')
            ->setParameter('requiresRegistration', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/bookings/edit.html.twig', [
            'booking' => new Booking(),
            'users' => $users,
            'services' => $services,
            'events' => $events,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_bookings_edit', requirements: ['id' => '\d+'])]
    public function edit(Booking $booking): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        $services = $this->entityManager->getRepository(Service::class)
            ->findBy(['status' => 'active', 'isBookable' => true]);
        $events = $this->entityManager->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.requiresRegistration = :requiresRegistration')
            ->setParameter('status', 'published')
            ->setParameter('requiresRegistration', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/bookings/edit.html.twig', [
            'booking' => $booking,
            'users' => $users,
            'services' => $services,
            'events' => $events,
            'isEdit' => true,
        ]);
    }

    #[Route('/save', name: 'admin_bookings_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $bookingId = $request->request->get('id');
        $booking = $bookingId ? $this->entityManager->getRepository(Booking::class)->find($bookingId) : new Booking();

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Set user
        $userId = $request->request->getInt('user_id');
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('admin_bookings_list');
        }
        $booking->setUser($user);

        // Set service or event
        $serviceId = $request->request->get('service_id');
        $eventId = $request->request->get('event_id');

        if ($serviceId && $eventId) {
            $this->addFlash('error', 'Cannot book both service and event');
            return $this->redirectToRoute('admin_bookings_list');
        }

        if ($serviceId) {
            $service = $this->entityManager->getRepository(Service::class)->find($serviceId);
            if ($service) {
                $booking->setService($service);
                $booking->setEvent(null);
            }
        } elseif ($eventId) {
            $event = $this->entityManager->getRepository(Event::class)->find($eventId);
            if ($event) {
                $booking->setEvent($event);
                $booking->setService(null);
            }
        }

        // Basic fields
        $booking->setStatus($request->request->get('status', 'pending'));
        $booking->setNotes($request->request->get('notes'));
        $booking->setParticipants($request->request->getInt('participants', 1));
        $booking->setTotalPrice($request->request->get('total_price'));
        $booking->setPaymentStatus($request->request->get('payment_status'));

        // Dates and times
        $bookingDate = $request->request->get('booking_date');
        if ($bookingDate) {
            $booking->setBookingDate(new \DateTimeImmutable($bookingDate));
        }

        $startTime = $request->request->get('start_time');
        if ($startTime) {
            $booking->setStartTime(new \DateTimeImmutable($startTime));
        }

        $endTime = $request->request->get('end_time');
        if ($endTime) {
            $booking->setEndTime(new \DateTimeImmutable($endTime));
        }

        // Customer info
        $customerInfo = [];
        $customerName = $request->request->get('customer_name');
        if ($customerName) {
            $customerInfo['name'] = $customerName;
        }
        $customerEmail = $request->request->get('customer_email');
        if ($customerEmail) {
            $customerInfo['email'] = $customerEmail;
        }
        $customerPhone = $request->request->get('customer_phone');
        if ($customerPhone) {
            $customerInfo['phone'] = $customerPhone;
        }
        if (!empty($customerInfo)) {
            $booking->setCustomerInfo($customerInfo);
        }

        if (!$bookingId) {
            $this->entityManager->persist($booking);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Booking saved successfully!');
        return $this->redirectToRoute('admin_bookings_edit', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_bookings_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Booking $booking): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $this->entityManager->remove($booking);
        $this->entityManager->flush();

        $this->addFlash('success', 'Booking deleted successfully!');
        return $this->redirectToRoute('admin_bookings_list');
    }

    #[Route('/{id}/status', name: 'admin_bookings_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(Booking $booking, Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('bookings')) {
            throw $this->createNotFoundException('Bookings module is not active');
        }

        $status = $request->request->get('status');
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];

        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status');
            return $this->redirectToRoute('admin_bookings_list');
        }

        $booking->setStatus($status);
        $this->entityManager->flush();

        $this->addFlash('success', 'Booking status updated successfully!');
        return $this->redirectToRoute('admin_bookings_list');
    }

    private function getBookingStats(): array
    {
        $repository = $this->entityManager->getRepository(Booking::class);
        
        return [
            'total' => $repository->count([]),
            'pending' => $repository->count(['status' => 'pending']),
            'confirmed' => $repository->count(['status' => 'confirmed']),
            'completed' => $repository->count(['status' => 'completed']),
            'cancelled' => $repository->count(['status' => 'cancelled']),
            'thisMonth' => $repository->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->where('b.createdAt >= :start')
                ->setParameter('start', new \DateTimeImmutable('first day of this month'))
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }
}