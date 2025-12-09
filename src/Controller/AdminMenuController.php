<?php

namespace App\Controller;

use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Repository\MenuRepository;
use App\Repository\MenuItemRepository;
use App\Repository\PageRepository;
use App\Service\MenuManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/menus', name: 'admin_menus_')]
#[IsGranted('ROLE_ADMIN')]
class AdminMenuController extends AbstractController
{
    public function __construct(
        private MenuManager $menuManager,
        private MenuRepository $menuRepository,
        private MenuItemRepository $menuItemRepository,
        private PageRepository $pageRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $menus = $this->menuRepository->findBy([], ['position' => 'ASC']);

        return $this->render('admin/menus/index.html.twig', [
            'menus' => $menus,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $location = $request->request->get('location');

            if ($name && $location) {
                // Check if location already exists
                $existing = $this->menuRepository->findByLocation($location);
                if ($existing) {
                    $this->addFlash('error', 'Un menu avec cet identifiant existe déjà.');
                    return $this->redirectToRoute('admin_menus_create');
                }

                $menu = $this->menuManager->createMenu($name, $location);
                $this->addFlash('success', 'Menu créé avec succès.');
                return $this->redirectToRoute('admin_menus_edit', ['id' => $menu->getId()]);
            }

            $this->addFlash('error', 'Veuillez remplir tous les champs.');
        }

        return $this->render('admin/menus/create.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Menu $menu): Response
    {
        $pages = $this->pageRepository->findBy(['status' => 'published'], ['title' => 'ASC']);
        $availableRoutes = $this->getAvailableRoutes();

        return $this->render('admin/menus/edit.html.twig', [
            'menu' => $menu,
            'pages' => $pages,
            'availableRoutes' => $availableRoutes,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Menu $menu): Response
    {
        $this->menuManager->deleteMenu($menu);
        $this->addFlash('success', 'Menu supprimé avec succès.');

        return $this->redirectToRoute('admin_menus_index');
    }

    #[Route('/{id}/items/add', name: 'items_add', methods: ['POST'])]
    public function addItem(Menu $menu, Request $request): Response
    {
        $label = $request->request->get('label');
        $type = $request->request->get('type');
        $parentId = $request->request->get('parent_id');

        if (!$label || !$type) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            return $this->redirectToRoute('admin_menus_edit', ['id' => $menu->getId()]);
        }

        $parent = $parentId ? $this->menuItemRepository->find($parentId) : null;

        $options = [
            'icon' => $request->request->get('icon'),
            'cssClass' => $request->request->get('css_class'),
            'openInNewTab' => $request->request->getBoolean('open_new_tab'),
        ];

        switch ($type) {
            case MenuItem::TYPE_ROUTE:
                $options['route'] = $request->request->get('route');
                $routeParams = $request->request->get('route_params');
                if ($routeParams) {
                    $options['routeParams'] = json_decode($routeParams, true) ?? [];
                }
                break;

            case MenuItem::TYPE_PAGE:
                $pageId = $request->request->get('page_id');
                if ($pageId) {
                    $options['page'] = $this->pageRepository->find($pageId);
                }
                break;

            case MenuItem::TYPE_URL:
                $options['customUrl'] = $request->request->get('custom_url');
                break;
        }

        // Handle roles
        $roles = $request->request->all('roles');
        if (!empty($roles)) {
            $options['roles'] = $roles;
        }

        $this->menuManager->createMenuItem($menu, $label, $type, $parent, $options);
        $this->addFlash('success', 'Élément ajouté avec succès.');

        return $this->redirectToRoute('admin_menus_edit', ['id' => $menu->getId()]);
    }

    #[Route('/items/{id}/edit', name: 'items_edit', methods: ['GET', 'POST'])]
    public function editItem(MenuItem $item, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $item->setLabel($request->request->get('label'));
            $item->setType($request->request->get('type'));
            $item->setIcon($request->request->get('icon'));
            $item->setCssClass($request->request->get('css_class'));
            $item->setOpenInNewTab($request->request->getBoolean('open_new_tab'));
            $item->setActive($request->request->getBoolean('active', true));

            switch ($item->getType()) {
                case MenuItem::TYPE_ROUTE:
                    $item->setRoute($request->request->get('route'));
                    $routeParams = $request->request->get('route_params');
                    $item->setRouteParams($routeParams ? json_decode($routeParams, true) : null);
                    $item->setPage(null);
                    $item->setCustomUrl(null);
                    break;

                case MenuItem::TYPE_PAGE:
                    $pageId = $request->request->get('page_id');
                    $item->setPage($pageId ? $this->pageRepository->find($pageId) : null);
                    $item->setRoute(null);
                    $item->setRouteParams(null);
                    $item->setCustomUrl(null);
                    break;

                case MenuItem::TYPE_URL:
                    $item->setCustomUrl($request->request->get('custom_url'));
                    $item->setRoute(null);
                    $item->setRouteParams(null);
                    $item->setPage(null);
                    break;

                case MenuItem::TYPE_DROPDOWN:
                    $item->setRoute(null);
                    $item->setRouteParams(null);
                    $item->setPage(null);
                    $item->setCustomUrl(null);
                    break;
            }

            // Handle roles
            $roles = $request->request->all('roles');
            $item->setRoles(!empty($roles) ? $roles : null);

            // Handle parent change
            $parentId = $request->request->get('parent_id');
            if ($parentId === '') {
                $item->setParent(null);
            } elseif ($parentId) {
                $newParent = $this->menuItemRepository->find($parentId);
                if ($newParent && $newParent->getId() !== $item->getId()) {
                    $item->setParent($newParent);
                }
            }

            $this->em->flush();
            $this->addFlash('success', 'Élément modifié avec succès.');

            return $this->redirectToRoute('admin_menus_edit', ['id' => $item->getMenu()->getId()]);
        }

        $pages = $this->pageRepository->findBy(['status' => 'published'], ['title' => 'ASC']);
        $availableRoutes = $this->getAvailableRoutes();

        // Get potential parents (excluding self and children)
        $potentialParents = $this->menuItemRepository->findBy([
            'menu' => $item->getMenu(),
        ]);
        $potentialParents = array_filter($potentialParents, function ($p) use ($item) {
            return $p->getId() !== $item->getId() && !$this->isDescendant($p, $item);
        });

        return $this->render('admin/menus/edit_item.html.twig', [
            'item' => $item,
            'pages' => $pages,
            'availableRoutes' => $availableRoutes,
            'potentialParents' => $potentialParents,
        ]);
    }

    #[Route('/items/{id}/delete', name: 'items_delete', methods: ['POST'])]
    public function deleteItem(MenuItem $item): Response
    {
        $menuId = $item->getMenu()->getId();
        $this->menuManager->deleteItem($item);
        $this->addFlash('success', 'Élément supprimé avec succès.');

        return $this->redirectToRoute('admin_menus_edit', ['id' => $menuId]);
    }

    #[Route('/{id}/reorder', name: 'reorder', methods: ['POST'])]
    public function reorder(Menu $menu, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $items = $data['items'] ?? [];

        foreach ($items as $position => $itemData) {
            $item = $this->menuItemRepository->find($itemData['id']);
            if ($item && $item->getMenu()->getId() === $menu->getId()) {
                $item->setPosition($position);

                // Update parent if provided
                if (isset($itemData['parent_id'])) {
                    $parent = $itemData['parent_id'] ? $this->menuItemRepository->find($itemData['parent_id']) : null;
                    $item->setParent($parent);
                }
            }
        }

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/items/{id}/toggle', name: 'items_toggle', methods: ['POST'])]
    public function toggleItem(MenuItem $item): JsonResponse
    {
        $item->setActive(!$item->isActive());
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'active' => $item->isActive(),
        ]);
    }

    /**
     * Check if $potential is a descendant of $item
     */
    private function isDescendant(MenuItem $potential, MenuItem $item): bool
    {
        foreach ($item->getChildren() as $child) {
            if ($child->getId() === $potential->getId()) {
                return true;
            }
            if ($this->isDescendant($potential, $child)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get available routes for menu items
     */
    private function getAvailableRoutes(): array
    {
        return [
            'public_home' => 'Accueil',
            'public_calendar' => 'Calendrier',
            'app_blog' => 'Blog / Actualités',
            'app_contact' => 'Contact',
            'app_login' => 'Connexion',
            'app_register' => 'Inscription',
            'member_profile' => 'Mon profil',
            'member_events' => 'Mes événements',
            'admin_dashboard' => 'Administration',
        ];
    }
}
