<?php

namespace App\Controller\Admin;

use App\Entity\EventType;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/event-types')]
#[IsGranted('ROLE_ADMIN')]
class EventTypeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ModuleManager $moduleManager,
        private SluggerInterface $slugger,
        private TranslatorInterface $translator
    ) {
    }

    #[Route('', name: 'admin_event_types_list')]
    public function list(): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        $eventTypes = $this->entityManager->getRepository(EventType::class)
            ->findBy([], ['sortOrder' => 'ASC']);

        return $this->render('admin/event_types/list.html.twig', [
            'eventTypes' => $eventTypes,
        ]);
    }

    #[Route('/new', name: 'admin_event_types_new')]
    public function new(): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        return $this->render('admin/event_types/edit.html.twig', [
            'eventType' => new EventType(),
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_types_edit', requirements: ['id' => '\d+'])]
    public function edit(EventType $eventType): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        return $this->render('admin/event_types/edit.html.twig', [
            'eventType' => $eventType,
            'isEdit' => true,
        ]);
    }

    #[Route('/save', name: 'admin_event_types_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        $eventTypeId = $request->request->get('id');
        $eventType = $eventTypeId ? $this->entityManager->getRepository(EventType::class)->find($eventTypeId) : new EventType();

        if (!$eventType) {
            throw $this->createNotFoundException('Type d\'événement non trouvé');
        }

        // Basic fields
        $eventType->setName($request->request->get('name'));
        
        // Set slug (use provided slug or generate from name)
        $slug = $request->request->get('slug');
        if (empty($slug)) {
            $slug = $this->slugger->slug($request->request->get('name'))->lower();
        } else {
            $slug = $this->slugger->slug($slug)->lower();
        }
        $eventType->setSlug($slug);

        $eventType->setDescription($request->request->get('description'));
        $eventType->setColor($request->request->get('color'));
        $eventType->setActive($request->request->getBoolean('active'));
        $eventType->setSortOrder($request->request->getInt('sort_order', 0));

        if (!$eventTypeId) {
            $this->entityManager->persist($eventType);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Type d\'événement sauvegardé avec succès');
        return $this->redirectToRoute('admin_event_types_edit', ['id' => $eventType->getId()]);
    }

    #[Route('/{id}/delete', name: 'admin_event_types_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(EventType $eventType): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        // Vérifier s'il y a des événements qui utilisent ce type
        $eventCount = $this->entityManager->createQuery(
            'SELECT COUNT(e.id) FROM App\Entity\Event e WHERE e.type = :type'
        )->setParameter('type', $eventType->getSlug())->getSingleScalarResult();

        if ($eventCount > 0) {
            $this->addFlash('error', "Impossible de supprimer ce type d'événement car il est utilisé par $eventCount événement(s)");
            return $this->redirectToRoute('admin_event_types_list');
        }

        $this->entityManager->remove($eventType);
        $this->entityManager->flush();

        $this->addFlash('success', 'Type d\'événement supprimé avec succès');
        return $this->redirectToRoute('admin_event_types_list');
    }

    #[Route('/{id}/toggle-active', name: 'admin_event_types_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(EventType $eventType): Response
    {
        if (!$this->moduleManager->isModuleActive('events')) {
            throw $this->createNotFoundException('Module Events non actif');
        }

        $eventType->setActive(!$eventType->isActive());
        $this->entityManager->flush();

        $status = $eventType->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Type d'événement $status avec succès");
        
        return $this->redirectToRoute('admin_event_types_list');
    }
}