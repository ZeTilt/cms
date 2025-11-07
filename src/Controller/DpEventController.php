<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Repository\EventRepository;
use App\Repository\EventTypeRepository;
use App\Repository\DivingLevelRepository;
use App\Repository\EventParticipationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dp/events')]
#[IsGranted('ROLE_DP')]
class DpEventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventTypeRepository $eventTypeRepository,
        private DivingLevelRepository $divingLevelRepository,
        private EventParticipationRepository $participationRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'dp_events_list')]
    public function index(): Response
    {
        $events = $this->eventRepository->findBy([], ['startDate' => 'DESC']);

        return $this->render('dp/events/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'dp_events_new')]
    public function new(Request $request): Response
    {
        $event = new Event();

        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setStartDate(new \DateTime($request->request->get('start_date')));

            if ($request->request->get('end_date')) {
                $event->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            $event->setLocation($request->request->get('location'));

            // Gérer le type d'événement
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('dp/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => true,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
                    'divingLevels' => $this->divingLevelRepository->findAll(),
                ]);
            }

            $eventType = $this->eventTypeRepository->find($eventTypeId);
            $event->setEventType($eventType);
            $event->setMaxParticipants($request->request->get('max_participants') ? (int)$request->request->get('max_participants') : null);

            // Gérer le niveau minimum requis
            $minDivingLevelId = $request->request->get('min_diving_level_id');
            if ($minDivingLevelId) {
                $minDivingLevel = $this->divingLevelRepository->find($minDivingLevelId);
                $event->setMinDivingLevel($minDivingLevel);
            }

            // Gérer les heures de RDV
            $clubMeetingTime = $request->request->get('club_meeting_time');
            if ($clubMeetingTime) {
                $event->setClubMeetingTime(new \DateTime($clubMeetingTime));
            }

            $siteMeetingTime = $request->request->get('site_meeting_time');
            if ($siteMeetingTime) {
                $event->setSiteMeetingTime(new \DateTime($siteMeetingTime));
            }

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');

            return $this->redirectToRoute('dp_events_list');
        }

        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAll();

        return $this->render('dp/events/edit.html.twig', [
            'event' => $event,
            'isNew' => true,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/edit', name: 'dp_events_edit')]
    public function edit(Event $event, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setDescription($request->request->get('description'));
            $event->setStartDate(new \DateTime($request->request->get('start_date')));

            if ($request->request->get('end_date')) {
                $event->setEndDate(new \DateTime($request->request->get('end_date')));
            }

            $event->setLocation($request->request->get('location'));

            // Gérer le type d'événement
            $eventTypeId = $request->request->get('event_type_id');
            if (!$eventTypeId) {
                $this->addFlash('error', 'Vous devez sélectionner un type d\'événement.');
                return $this->render('dp/events/edit.html.twig', [
                    'event' => $event,
                    'isNew' => false,
                    'eventTypes' => $this->eventTypeRepository->findActive(),
                    'divingLevels' => $this->divingLevelRepository->findAll(),
                ]);
            }

            $eventType = $this->eventTypeRepository->find($eventTypeId);
            $event->setEventType($eventType);
            $event->setMaxParticipants($request->request->get('max_participants') ? (int)$request->request->get('max_participants') : null);

            // Gérer le niveau minimum requis
            $minDivingLevelId = $request->request->get('min_diving_level_id');
            if ($minDivingLevelId) {
                $minDivingLevel = $this->divingLevelRepository->find($minDivingLevelId);
                $event->setMinDivingLevel($minDivingLevel);
            } else {
                $event->setMinDivingLevel(null);
            }

            // Gérer les heures de RDV
            $clubMeetingTime = $request->request->get('club_meeting_time');
            if ($clubMeetingTime) {
                $event->setClubMeetingTime(new \DateTime($clubMeetingTime));
            } else {
                $event->setClubMeetingTime(null);
            }

            $siteMeetingTime = $request->request->get('site_meeting_time');
            if ($siteMeetingTime) {
                $event->setSiteMeetingTime(new \DateTime($siteMeetingTime));
            } else {
                $event->setSiteMeetingTime(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Événement mis à jour avec succès !');

            return $this->redirectToRoute('dp_events_list');
        }

        $eventTypes = $this->eventTypeRepository->findActive();
        $divingLevels = $this->divingLevelRepository->findAll();

        return $this->render('dp/events/edit.html.twig', [
            'event' => $event,
            'isNew' => false,
            'eventTypes' => $eventTypes,
            'divingLevels' => $divingLevels,
        ]);
    }

    #[Route('/{id}/participants', name: 'dp_events_participants')]
    #[IsGranted('ROLE_USER')]
    public function participants(Event $event): Response
    {
        // Vérifier que l'utilisateur connecté est le DP de l'événement ou un admin
        $user = $this->getUser();
        $isDp = $event->getDivingDirector() && $event->getDivingDirector()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        if (!$isDp && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous devez être le Directeur de Plongée de cet événement pour accéder à cette page.');
        }

        $activeParticipants = $event->getActiveParticipationsList();
        $waitingList = $event->getWaitingListParticipations();
        $clubMeetingParticipants = $event->getClubMeetingParticipants();
        $siteMeetingParticipants = $event->getSiteMeetingParticipants();

        return $this->render('dp/events/participants.html.twig', [
            'event' => $event,
            'activeParticipants' => $activeParticipants,
            'waitingList' => $waitingList,
            'clubMeetingParticipants' => $clubMeetingParticipants,
            'siteMeetingParticipants' => $siteMeetingParticipants,
        ]);
    }

    #[Route('/{id}/add-participant', name: 'dp_events_add_participant', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addParticipant(Event $event, Request $request): Response
    {
        // Vérifier que l'utilisateur connecté est le DP de l'événement ou un admin
        $user = $this->getUser();
        $isDp = $event->getDivingDirector() && $event->getDivingDirector()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        if (!$isDp && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous devez être le Directeur de Plongée de cet événement pour ajouter des participants.');
        }

        $userId = $request->request->get('user_id');

        if (!$userId) {
            $this->addFlash('error', 'Veuillez sélectionner un participant.');
            return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
        }

        // Vérifier si déjà inscrit
        $existingParticipation = $this->participationRepository->findByEventAndUser($event, $user);
        if ($existingParticipation && $existingParticipation->isActive()) {
            $this->addFlash('warning', $user->getFullName() . ' est déjà inscrit à cet événement.');
            return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
        }

        // Créer la participation
        $participation = new EventParticipation();
        $participation->setEvent($event);
        $participation->setParticipant($user);

        // Gérer le point de rendez-vous
        $meetingPoint = $request->request->get('meeting_point');
        if ($meetingPoint && in_array($meetingPoint, ['club', 'site'])) {
            $participation->setMeetingPoint($meetingPoint);
        }

        // Vérifier si l'événement est complet
        $isWaitingList = $event->isFullyBooked();
        $participation->setIsWaitingList($isWaitingList);

        $this->entityManager->persist($participation);
        $this->entityManager->flush();

        if ($isWaitingList) {
            $this->addFlash('warning', $user->getFullName() . ' a été ajouté à la liste d\'attente.');
        } else {
            $this->addFlash('success', $user->getFullName() . ' a été inscrit avec succès !');
        }

        return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
    }

    #[Route('/{id}/remove-participant/{participationId}', name: 'dp_events_remove_participant', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function removeParticipant(Event $event, int $participationId): Response
    {
        // Vérifier que l'utilisateur connecté est le DP de l'événement ou un admin
        $user = $this->getUser();
        $isDp = $event->getDivingDirector() && $event->getDivingDirector()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        if (!$isDp && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous devez être le Directeur de Plongée de cet événement pour retirer des participants.');
        }

        $participation = $this->participationRepository->find($participationId);

        if (!$participation || $participation->getEvent() !== $event) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
        }

        $participantName = $participation->getParticipant()->getFullName();

        // Annuler la participation
        $participation->setStatus('cancelled');

        // Promouvoir quelqu'un de la liste d'attente si nécessaire
        if (!$participation->isWaitingList()) {
            $waitingListParticipations = $event->getWaitingListParticipations();
            if (!$waitingListParticipations->isEmpty()) {
                $firstWaiting = $waitingListParticipations->first();
                $firstWaiting->setIsWaitingList(false);
                $this->addFlash('info', $firstWaiting->getParticipant()->getFullName() . ' a été promu de la liste d\'attente.');
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', $participantName . ' a été désinscrit.');

        return $this->redirectToRoute('dp_events_participants', ['id' => $event->getId()]);
    }

    #[Route('/{id}/export-csv', name: 'dp_events_export_csv')]
    #[IsGranted('ROLE_USER')]
    public function exportCsv(Event $event): Response
    {
        // Vérifier que l'utilisateur connecté est le DP de l'événement ou un admin
        $user = $this->getUser();
        $isDp = $event->getDivingDirector() && $event->getDivingDirector()->getId() === $user->getId();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_SUPER_ADMIN', $user->getRoles());

        if (!$isDp && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous devez être le Directeur de Plongée de cet événement pour exporter les participants.');
        }

        $activeParticipants = $event->getActiveParticipationsList();

        // Préparer le contenu CSV
        $csv = [];

        // En-têtes
        $csv[] = [
            'Nom',
            'Prénom',
            'Email',
            'Téléphone',
            'Niveau',
            'Type participation',
            'Certificat médical (validité)',
            'Point de RDV',
            'Nombre de places',
            'Date d\'inscription'
        ];

        // Données
        foreach ($activeParticipants as $participation) {
            $participant = $participation->getParticipant();

            // Calculer l'état du certificat médical
            $medicalCertStatus = 'Non renseigné';
            if ($participant->getMedicalCertificateExpiry()) {
                $expiryDate = $participant->getMedicalCertificateExpiry();
                $today = new \DateTime('today');
                $dateStr = $expiryDate->format('d/m/Y');

                if ($expiryDate < $today) {
                    $medicalCertStatus = $dateStr . ' (EXPIRÉ)';
                } elseif ($expiryDate < (new \DateTime('+30 days'))) {
                    $medicalCertStatus = $dateStr . ' (expire bientôt)';
                } else {
                    $medicalCertStatus = $dateStr;
                }
            }

            // Formater le téléphone pour conserver le 0 initial dans Excel
            $phone = $participant->getPhoneNumber();
            if ($phone) {
                // Préfixe avec tabulation pour forcer Excel à traiter comme du texte
                $phone = "\t" . $phone;
            } else {
                $phone = '';
            }

            // Type de participation
            $participationType = '';
            if ($participation->getParticipationType() === 'instructor') {
                $participationType = 'Encadrant';
            } elseif ($participation->getParticipationType() === 'autonomous') {
                $participationType = 'Autonome';
            }

            $csv[] = [
                $participant->getLastName() ?? '',
                $participant->getFirstName() ?? '',
                $participant->getEmail(),
                $phone,
                $participant->getHighestDivingLevel() ? $participant->getHighestDivingLevel()->getName() : '',
                $participationType,
                $medicalCertStatus,
                $participation->getMeetingPoint() === 'club' ? 'Club' : ($participation->getMeetingPoint() === 'site' ? 'Site' : ''),
                $participation->getQuantity() ?? 1,
                $participation->getRegistrationDate()->format('d/m/Y H:i')
            ];
        }

        // Générer le CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';'); // Utilise le point-virgule pour Excel français
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Ajouter le BOM UTF-8 pour Excel
        $csvContent = "\xEF\xBB\xBF" . $csvContent;

        $filename = sprintf(
            'participants_%s_%s.csv',
            preg_replace('/[^a-zA-Z0-9]/', '_', $event->getTitle()),
            $event->getStartDate()->format('Y-m-d')
        );

        return new Response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}

#[Route('/dp/api')]
#[IsGranted('ROLE_DP')]
class DpApiController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    #[Route('/users', name: 'dp_api_users', methods: ['GET'])]
    public function getUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        $usersData = [];

        foreach ($users as $user) {
            $usersData[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getFullName(),
            ];
        }

        return new JsonResponse($usersData);
    }
}