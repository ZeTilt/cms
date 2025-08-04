<?php

namespace App\Controller\Admin;

use App\Entity\Widget;
use App\Form\WidgetType;
use App\Repository\WidgetRepository;
use App\Service\WidgetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/widgets', name: 'admin_widgets_')]
#[IsGranted('ROLE_ADMIN')]
class WidgetController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WidgetRepository $widgetRepository,
        private WidgetService $widgetService
    ) {}

    #[Route('/', name: 'list')]
    public function list(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $category = $request->query->get('category', '');
        $type = $request->query->get('type', '');

        $qb = $this->widgetRepository->createQueryBuilder('w')
            ->leftJoin('w.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('w.category', 'ASC')
            ->addOrderBy('w.title', 'ASC');

        if ($search) {
            $qb->andWhere('w.title LIKE :search OR w.description LIKE :search OR w.name LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category) {
            $qb->andWhere('w.category = :category')
               ->setParameter('category', $category);
        }

        if ($type) {
            $qb->andWhere('w.type = :type')
               ->setParameter('type', $type);
        }

        $widgets = $qb->getQuery()->getResult();
        $stats = $this->widgetRepository->getStats();

        return $this->render('admin/widgets/list.html.twig', [
            'widgets' => $widgets,
            'stats' => $stats,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedType' => $type,
            'availableTypes' => Widget::getAvailableTypes(),
            'availableCategories' => Widget::getAvailableCategories()
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        $widget = new Widget();
        $widget->setCreatedBy($this->getUser());

        $form = $this->createForm(WidgetType::class, $widget);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valider le contenu
            $errors = $this->widgetService->validateWidgetContent($widget);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('admin/widgets/create.html.twig', [
                    'form' => $form->createView(),
                    'widget' => $widget
                ]);
            }

            // Vérifier l'unicité du nom
            if ($this->widgetRepository->nameExists($widget->getName())) {
                $this->addFlash('error', 'Un widget avec ce nom existe déjà.');
                return $this->render('admin/widgets/create.html.twig', [
                    'form' => $form->createView(),
                    'widget' => $widget
                ]);
            }

            $this->entityManager->persist($widget);
            $this->entityManager->flush();

            $this->addFlash('success', 'Widget créé avec succès !');
            return $this->redirectToRoute('admin_widgets_show', ['id' => $widget->getId()]);
        }

        return $this->render('admin/widgets/create.html.twig', [
            'form' => $form->createView(),
            'widget' => $widget
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Widget $widget): Response
    {
        $renderedContent = $this->widgetService->renderWidgetEntity($widget);

        return $this->render('admin/widgets/show.html.twig', [
            'widget' => $widget,
            'renderedContent' => $renderedContent
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Widget $widget): Response
    {
        $form = $this->createForm(WidgetType::class, $widget);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valider le contenu
            $errors = $this->widgetService->validateWidgetContent($widget);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('admin/widgets/edit.html.twig', [
                    'form' => $form->createView(),
                    'widget' => $widget
                ]);
            }

            // Vérifier l'unicité du nom (exclure l'ID actuel)
            if ($this->widgetRepository->nameExists($widget->getName(), $widget->getId())) {
                $this->addFlash('error', 'Un widget avec ce nom existe déjà.');
                return $this->render('admin/widgets/edit.html.twig', [
                    'form' => $form->createView(),
                    'widget' => $widget
                ]);
            }

            // Vider le cache du widget
            $this->widgetService->clearWidgetCache($widget);

            $this->entityManager->flush();

            $this->addFlash('success', 'Widget mis à jour avec succès !');
            return $this->redirectToRoute('admin_widgets_show', ['id' => $widget->getId()]);
        }

        return $this->render('admin/widgets/edit.html.twig', [
            'form' => $form->createView(),
            'widget' => $widget
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Widget $widget): Response
    {
        if ($this->isCsrfTokenValid('delete_widget_' . $widget->getId(), $request->request->get('_token'))) {
            $this->widgetService->clearWidgetCache($widget);
            $this->entityManager->remove($widget);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Widget supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_widgets_list');
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Request $request, Widget $widget): Response
    {
        if ($this->isCsrfTokenValid('toggle_widget_' . $widget->getId(), $request->request->get('_token'))) {
            $widget->setActive(!$widget->isActive());
            $this->widgetService->clearWidgetCache($widget);
            $this->entityManager->flush();
            
            $status = $widget->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Widget {$status} avec succès !");
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_widgets_show', ['id' => $widget->getId()]);
    }

    #[Route('/{id}/preview', name: 'preview', requirements: ['id' => '\d+'])]
    public function preview(Widget $widget): Response
    {
        $renderedContent = $this->widgetService->renderWidgetEntity($widget);

        return $this->render('admin/widgets/preview.html.twig', [
            'widget' => $widget,
            'renderedContent' => $renderedContent
        ]);
    }

    #[Route('/{id}/clear-cache', name: 'clear_cache', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function clearCache(Request $request, Widget $widget): Response
    {
        if ($this->isCsrfTokenValid('clear_cache_' . $widget->getId(), $request->request->get('_token'))) {
            $this->widgetService->clearWidgetCache($widget);
            $this->addFlash('success', 'Cache du widget vidé avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_widgets_show', ['id' => $widget->getId()]);
    }

    #[Route('/categories', name: 'categories')]
    public function categories(): Response
    {
        $stats = $this->widgetRepository->getStats();
        
        return $this->render('admin/widgets/categories.html.twig', [
            'categories' => Widget::getAvailableCategories(),
            'stats' => $stats
        ]);
    }
}