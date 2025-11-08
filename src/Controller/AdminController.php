<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Repository\EventParticipationRepository;
use App\Service\ModuleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        EventRepository $eventRepository,
        EventParticipationRepository $participationRepository
    ): Response
    {
        // Stats utilisateurs
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['status' => 'active']);
        $pendingUsers = $userRepository->count(['status' => 'pending']);

        // Nouveaux utilisateurs ce mois
        $thisMonth = new \DateTime('first day of this month');
        $newUsersThisMonth = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :thisMonth')
            ->setParameter('thisMonth', $thisMonth)
            ->getQuery()
            ->getSingleScalarResult();

        // Stats événements
        $now = new \DateTime();
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.startDate >= :now')
            ->andWhere('e.status = :status')
            ->setParameter('now', $now)
            ->setParameter('status', 'active')
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalUpcomingEvents = $eventRepository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.startDate >= :now')
            ->andWhere('e.status = :status')
            ->setParameter('now', $now)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        // Total participants inscrits (événements à venir)
        $totalParticipations = $participationRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.event', 'e')
            ->where('e.startDate >= :now')
            ->andWhere('e.status = :status')
            ->andWhere('p.status = :participationStatus')
            ->setParameter('now', $now)
            ->setParameter('status', 'active')
            ->setParameter('participationStatus', 'confirmed')
            ->getQuery()
            ->getSingleScalarResult();

        // Événements nécessitant attention
        $alertEvents = [];
        foreach ($upcomingEvents as $event) {
            $confirmedCount = count($event->getActiveParticipationsList());
            $maxParticipants = $event->getMaxParticipants();

            // Événement presque vide (moins de 3 inscrits et J-7)
            $daysUntil = $now->diff($event->getStartDate())->days;
            if ($confirmedCount < 3 && $daysUntil <= 7) {
                $alertEvents[] = [
                    'event' => $event,
                    'type' => 'low_registrations',
                    'message' => sprintf('%d participant(s) seulement', $confirmedCount)
                ];
            }

            // Événement presque complet (>90% ou -2 places)
            if ($maxParticipants && ($confirmedCount >= $maxParticipants - 2 || $confirmedCount >= $maxParticipants * 0.9)) {
                $remaining = $maxParticipants - $confirmedCount;
                $alertEvents[] = [
                    'event' => $event,
                    'type' => 'almost_full',
                    'message' => sprintf('Plus que %d place(s)', $remaining)
                ];
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'pending' => $pendingUsers,
                    'new_this_month' => $newUsersThisMonth,
                ],
                'events' => [
                    'upcoming_count' => $totalUpcomingEvents,
                    'total_participants' => $totalParticipations,
                ],
            ],
            'upcoming_events' => $upcomingEvents,
            'alert_events' => $alertEvents,
        ]);
    }

    #[Route('/modules', name: 'admin_modules')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function modules(ModuleManager $moduleManager): Response
    {
        $allModules = $moduleManager->getAllModules();
        
        return $this->render('admin/modules.html.twig', [
            'modules' => $allModules,
        ]);
    }

    #[Route('/modules/{moduleName}/toggle', name: 'admin_modules_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function toggleModule(string $moduleName, Request $request, ModuleManager $moduleManager): JsonResponse
    {
        $action = $request->request->get('action'); // 'activate' or 'deactivate'
        
        if (!in_array($action, ['activate', 'deactivate'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }

        $success = $action === 'activate' 
            ? $moduleManager->activateModule($moduleName)
            : $moduleManager->deactivateModule($moduleName);

        if ($success) {
            $module = $moduleManager->getModule($moduleName);
            return new JsonResponse([
                'success' => true,
                'message' => sprintf('Module %s %s successfully', $moduleName, $action === 'activate' ? 'activated' : 'deactivated'),
                'active' => $module->isActive()
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Module not found'], 404);
    }
}