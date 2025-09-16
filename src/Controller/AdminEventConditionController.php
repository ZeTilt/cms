<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventCondition;
use App\Repository\EventRepository;
use App\Repository\EventConditionRepository;
use App\Service\EventConditionService;
use App\Service\EntityIntrospectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin/events/{eventId}/conditions', name: 'admin_event_conditions_')]
class AdminEventConditionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private EventConditionRepository $conditionRepository,
        private EventConditionService $conditionService,
        private EntityIntrospectionService $introspectionService
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $eventId): Response
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        $conditions = $this->conditionRepository->findActiveByEvent($event);

        return $this->render('admin/events/conditions/index.html.twig', [
            'event' => $event,
            'conditions' => $conditions,
            'availableEntities' => $this->conditionService->getAvailableEntities(),
            'availableOperators' => $this->conditionService->getAvailableOperators(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(int $eventId, Request $request): Response
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé');
        }

        if ($request->isMethod('POST')) {
            $entityClass = $request->request->get('entity_class');
            $attributeName = $request->request->get('attribute_name');
            $operator = $request->request->get('operator');
            $value = $request->request->get('value');
            $errorMessage = $request->request->get('error_message');

            $condition = $this->conditionService->createCondition(
                $event,
                $entityClass,
                $attributeName,
                $operator,
                $value,
                $errorMessage
            );

            $errors = $this->conditionService->validateCondition($condition);
            
            if (empty($errors)) {
                $this->entityManager->persist($condition);
                $this->entityManager->flush();

                $this->addFlash('success', 'Condition ajoutée avec succès');
                return $this->redirectToRoute('admin_event_conditions_index', ['eventId' => $eventId]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('admin/events/conditions/new.html.twig', [
            'event' => $event,
            'availableEntities' => $this->conditionService->getAvailableEntities(),
            'availableOperators' => $this->conditionService->getAvailableOperators(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $eventId, int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($eventId);
        $condition = $this->conditionRepository->find($id);
        
        if (!$event || !$condition || $condition->getEvent() !== $event) {
            throw $this->createNotFoundException('Condition non trouvée');
        }

        if ($request->isMethod('POST')) {
            $condition->setEntityClass($request->request->get('entity_class'))
                ->setAttributeName($request->request->get('attribute_name'))
                ->setOperator($request->request->get('operator'))
                ->setValue($request->request->get('value'))
                ->setErrorMessage($request->request->get('error_message'))
                ->setActive($request->request->has('is_active'));

            $errors = $this->conditionService->validateCondition($condition);
            
            if (empty($errors)) {
                $this->entityManager->flush();

                $this->addFlash('success', 'Condition modifiée avec succès');
                return $this->redirectToRoute('admin_event_conditions_index', ['eventId' => $eventId]);
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->render('admin/events/conditions/edit.html.twig', [
            'event' => $event,
            'condition' => $condition,
            'availableEntities' => $this->conditionService->getAvailableEntities(),
            'availableOperators' => $this->conditionService->getAvailableOperators(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $eventId, int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($eventId);
        $condition = $this->conditionRepository->find($id);
        
        if (!$event || !$condition || $condition->getEvent() !== $event) {
            throw $this->createNotFoundException('Condition non trouvée');
        }

        if ($this->isCsrfTokenValid('delete_condition_' . $id, $request->request->get('_token'))) {
            $this->entityManager->remove($condition);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Condition supprimée avec succès');
        }

        return $this->redirectToRoute('admin_event_conditions_index', ['eventId' => $eventId]);
    }

    #[Route('/attributes/{entityClass}', name: 'get_attributes', methods: ['GET'])]
    public function getAttributes(string $entityClass): JsonResponse
    {
        try {
            // Nettoyer les backslashes échappés
            $entityClass = str_replace('\\\\', '\\', $entityClass);
            
            // Version simplifiée pour debug
            $attributes = [];
            
            if ($entityClass === 'App\\Entity\\User') {
                $attributes = [
                    'firstName' => 'Prénom',
                    'lastName' => 'Nom', 
                    'email' => 'Email',
                    'status' => 'Statut',
                    'active' => 'Actif',
                    'emailVerified' => 'Email vérifié',
                    'diving_level' => 'Niveau de plongée',
                    'birth_date' => 'Date de naissance',
                    'medical_certificate_date' => 'Date certificat médical',
                    'freediver' => 'Apnéiste'
                ];
            } elseif ($entityClass === 'App\\Entity\\Event') {
                $attributes = [
                    'title' => 'Titre',
                    'type' => 'Type',
                    'status' => 'Statut',
                    'maxParticipants' => 'Nombre max participants',
                    'location' => 'Lieu'
                ];
            }
            
            return $this->json([
                'attributes' => $attributes,
                'debug' => [
                    'entityClass' => $entityClass,
                    'method' => 'simplified'
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attributes' => []
            ], 500);
        }
    }

    #[Route('/values/{entityClass}/{attributeName}', name: 'get_values', methods: ['GET'])]
    public function getValues(string $entityClass, string $attributeName): JsonResponse
    {
        try {
            // Nettoyer les backslashes échappés
            $entityClass = str_replace('\\\\', '\\', $entityClass);
            
            // Version simplifiée pour debug
            $values = [];
            $operators = [
                '=' => 'Égal à',
                '!=' => 'Différent de',
                '>' => 'Supérieur à',
                '>=' => 'Supérieur ou égal à',
                '<' => 'Inférieur à',
                '<=' => 'Inférieur ou égal à',
                'contains' => 'Contient',
                'in' => 'Dans la liste'
            ];
            
            // Valeurs spécifiques selon l'attribut
            if ($entityClass === 'App\\Entity\\User') {
                switch ($attributeName) {
                    case 'diving_level':
                        $values = [
                            'N1' => 'N1 - Plongeur Encadré 20m',
                            'N2' => 'N2 - Plongeur Autonome 20m',
                            'N3' => 'N3 - Plongeur Autonome 60m',
                            'N4' => 'N4 - Guide de Palanquée',
                            'E1' => 'E1 - Initiateur',
                            'E2' => 'E2 - Moniteur Fédéral 1er',
                            'RIFAP' => 'RIFAP'
                        ];
                        $operators = ['=' => 'Égal à', '>=' => 'Supérieur ou égal à', 'in' => 'Dans la liste'];
                        break;
                    case 'status':
                        $values = [
                            'pending' => 'En attente',
                            'approved' => 'Approuvé',
                            'rejected' => 'Rejeté'
                        ];
                        $operators = ['=' => 'Égal à', '!=' => 'Différent de'];
                        break;
                    case 'active':
                    case 'emailVerified':
                    case 'freediver':
                        $values = [
                            '1' => 'Oui',
                            '0' => 'Non'
                        ];
                        $operators = ['=' => 'Égal à'];
                        break;
                }
            }
            
            return $this->json([
                'values' => $values,
                'suggested_operators' => $operators,
                'debug' => [
                    'entityClass' => $entityClass,
                    'attributeName' => $attributeName,
                    'method' => 'simplified'
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'values' => [],
                'suggested_operators' => []
            ], 500);
        }
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(int $eventId, int $id, Request $request): Response
    {
        $event = $this->eventRepository->find($eventId);
        $condition = $this->conditionRepository->find($id);
        
        if (!$event || !$condition || $condition->getEvent() !== $event) {
            throw $this->createNotFoundException('Condition non trouvée');
        }

        if ($this->isCsrfTokenValid('toggle_condition_' . $id, $request->request->get('_token'))) {
            $condition->setActive(!$condition->isActive());
            $this->entityManager->flush();
            
            $status = $condition->isActive() ? 'activée' : 'désactivée';
            $this->addFlash('success', "Condition {$status} avec succès");
        }

        return $this->redirectToRoute('admin_event_conditions_index', ['eventId' => $eventId]);
    }
}