<?php

namespace App\Controller;

use App\Entity\EventType;
use App\Repository\EventTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/event-types')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventTypeController extends AbstractController
{
    public function __construct(
        private EventTypeRepository $eventTypeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_event_types_list')]
    public function index(): Response
    {
        $eventTypes = $this->eventTypeRepository->findBy([], ['name' => 'ASC']);
        
        return $this->render('admin/event_types/index.html.twig', [
            'eventTypes' => $eventTypes,
        ]);
    }

    #[Route('/new', name: 'admin_event_types_new')]
    public function new(Request $request): Response
    {
        $eventType = new EventType();
        
        // Générer une couleur aléatoire pour le nouveau type
        $eventType->setColor($this->generateRandomColor());
        
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $code = $this->slugify($name);
            
            $eventType->setName($name);
            $eventType->setCode($code);
            $eventType->setColor($request->request->get('color', '#FD7E29'));
            $eventType->setDescription($request->request->get('description'));
            $eventType->setActive($request->request->getBoolean('is_active', true));
            $eventType->setRequiresDivingDirector($request->request->getBoolean('requires_diving_director', false));
            $eventType->setRequiresPilot($request->request->getBoolean('requires_pilot', false));

            // Vérifier que le code est unique
            $existingType = $this->eventTypeRepository->findByCode($code);
            if ($existingType) {
                $this->addFlash('error', 'Un type d\'événement avec ce nom existe déjà (code: ' . $code . ').');
                return $this->render('admin/event_types/edit.html.twig', [
                    'eventType' => $eventType,
                    'isNew' => true,
                ]);
            }
            
            $this->entityManager->persist($eventType);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Type d\'événement créé avec succès !');
            
            return $this->redirectToRoute('admin_event_types_list');
        }
        
        return $this->render('admin/event_types/edit.html.twig', [
            'eventType' => $eventType,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_types_edit')]
    public function edit(EventType $eventType, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $newCode = $this->slugify($name);
            
            // Vérifier que le code est unique (sauf pour l'objet actuel)
            if ($newCode !== $eventType->getCode()) {
                $existingType = $this->eventTypeRepository->findByCode($newCode);
                if ($existingType) {
                    $this->addFlash('error', 'Un type d\'événement avec ce nom existe déjà (code: ' . $newCode . ').');
                    return $this->render('admin/event_types/edit.html.twig', [
                        'eventType' => $eventType,
                        'isNew' => false,
                    ]);
                }
            }
            
            $eventType->setName($name);
            $eventType->setCode($newCode);
            $eventType->setColor($request->request->get('color', '#FD7E29'));
            $eventType->setDescription($request->request->get('description'));
            $eventType->setActive($request->request->getBoolean('is_active', true));
            $eventType->setRequiresDivingDirector($request->request->getBoolean('requires_diving_director', false));
            $eventType->setRequiresPilot($request->request->getBoolean('requires_pilot', false));

            $this->entityManager->flush();
            
            $this->addFlash('success', 'Type d\'événement mis à jour avec succès !');
            
            return $this->redirectToRoute('admin_event_types_list');
        }
        
        return $this->render('admin/event_types/edit.html.twig', [
            'eventType' => $eventType,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_event_types_delete')]
    public function delete(EventType $eventType): Response
    {
        // Vérifier qu'aucun événement n'utilise ce type
        if ($eventType->getEvents()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce type car il est utilisé par ' . $eventType->getEvents()->count() . ' événement(s).');
            return $this->redirectToRoute('admin_event_types_list');
        }
        
        $this->entityManager->remove($eventType);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Type d\'événement supprimé avec succès !');
        
        return $this->redirectToRoute('admin_event_types_list');
    }

    #[Route('/{id}/toggle', name: 'admin_event_types_toggle')]
    public function toggle(EventType $eventType): Response
    {
        $eventType->setActive(!$eventType->isActive());
        $this->entityManager->flush();
        
        $status = $eventType->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Type d'événement {$status} avec succès !");
        
        return $this->redirectToRoute('admin_event_types_list');
    }

    private function slugify(string $text): string
    {
        // Convertir en minuscules
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remplacer les caractères accentués
        $text = str_replace(
            ['à', 'á', 'â', 'ã', 'ä', 'å', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ÿ', 'ñ', 'ç'],
            ['a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'n', 'c'],
            $text
        );
        
        // Enlever les caractères non alphanumériques sauf espaces et tirets
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        
        // Remplacer les espaces et tirets multiples par un seul underscore
        $text = preg_replace('/[\s-]+/', '_', $text);
        
        // Enlever les underscores en début et fin
        $text = trim($text, '_');
        
        return $text;
    }

    private function generateRandomColor(): string
    {
        // Palette de couleurs prédéfinies pour les événements
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Emerald
            '#8B5CF6', // Purple
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#06B6D4', // Cyan
            '#84CC16', // Lime
            '#EC4899', // Pink
            '#6366F1', // Indigo
            '#14B8A6', // Teal
            '#F97316', // Orange
            '#A855F7', // Violet
            '#22C55E', // Green
            '#F43F5E', // Rose
            '#0EA5E9', // Sky
            '#65A30D', // Green-600
        ];
        
        // Récupérer les couleurs déjà utilisées
        $existingColors = array_map(
            fn($eventType) => $eventType->getColor(),
            $this->eventTypeRepository->findAll()
        );
        
        // Filtrer les couleurs non utilisées
        $availableColors = array_diff($colors, $existingColors);
        
        // Si toutes les couleurs sont utilisées, utiliser la palette complète
        if (empty($availableColors)) {
            $availableColors = $colors;
        }
        
        // Retourner une couleur aléatoire
        return $availableColors[array_rand($availableColors)];
    }
}