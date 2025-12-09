<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\MenuItem;
use App\Repository\MenuRepository;
use App\Repository\MenuItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MenuManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private MenuRepository $menuRepository,
        private MenuItemRepository $menuItemRepository,
        private Security $security
    ) {
    }

    /**
     * Get a menu by its location
     */
    public function getMenu(string $location): ?Menu
    {
        return $this->menuRepository->findByLocation($location);
    }

    /**
     * Get menu items for a location, filtered by user roles
     */
    public function getMenuItems(string $location): array
    {
        $menu = $this->getMenu($location);
        if (!$menu) {
            return [];
        }

        $user = $this->security->getUser();
        $userRoles = $user ? $user->getRoles() : [];

        return $this->filterItemsByRoles($menu->getRootItems()->toArray(), $userRoles);
    }

    /**
     * Filter menu items by user roles recursively
     */
    private function filterItemsByRoles(array $items, array $userRoles): array
    {
        $filtered = [];
        foreach ($items as $item) {
            if (!$item->isActive()) {
                continue;
            }

            if (!$item->isAccessibleBy($userRoles)) {
                continue;
            }

            $filtered[] = [
                'item' => $item,
                'children' => $this->filterItemsByRoles(
                    $item->getActiveChildren()->toArray(),
                    $userRoles
                ),
            ];
        }

        return $filtered;
    }

    /**
     * Create a new menu
     */
    public function createMenu(string $name, string $location): Menu
    {
        $menu = new Menu();
        $menu->setName($name);
        $menu->setLocation($location);

        $this->em->persist($menu);
        $this->em->flush();

        return $menu;
    }

    /**
     * Create a menu item
     */
    public function createMenuItem(
        Menu $menu,
        string $label,
        string $type,
        ?MenuItem $parent = null,
        array $options = []
    ): MenuItem {
        $item = new MenuItem();
        $item->setMenu($menu);
        $item->setLabel($label);
        $item->setType($type);
        $item->setParent($parent);
        $item->setPosition($this->menuItemRepository->getNextPosition($menu, $parent));

        if (isset($options['route'])) {
            $item->setRoute($options['route']);
        }
        if (isset($options['routeParams'])) {
            $item->setRouteParams($options['routeParams']);
        }
        if (isset($options['customUrl'])) {
            $item->setCustomUrl($options['customUrl']);
        }
        if (isset($options['page'])) {
            $item->setPage($options['page']);
        }
        if (isset($options['icon'])) {
            $item->setIcon($options['icon']);
        }
        if (isset($options['roles'])) {
            $item->setRoles($options['roles']);
        }
        if (isset($options['cssClass'])) {
            $item->setCssClass($options['cssClass']);
        }
        if (isset($options['openInNewTab'])) {
            $item->setOpenInNewTab($options['openInNewTab']);
        }
        if (isset($options['active'])) {
            $item->setActive($options['active']);
        }

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    /**
     * Update menu item positions
     */
    public function reorderItems(array $itemIds): void
    {
        foreach ($itemIds as $position => $itemId) {
            $item = $this->menuItemRepository->find($itemId);
            if ($item) {
                $item->setPosition($position);
            }
        }
        $this->em->flush();
    }

    /**
     * Move item to a new parent
     */
    public function moveItem(MenuItem $item, ?MenuItem $newParent): void
    {
        $item->setParent($newParent);
        $item->setPosition($this->menuItemRepository->getNextPosition($item->getMenu(), $newParent));
        $this->em->flush();
    }

    /**
     * Delete a menu item
     */
    public function deleteItem(MenuItem $item): void
    {
        $this->em->remove($item);
        $this->em->flush();
    }

    /**
     * Delete a menu
     */
    public function deleteMenu(Menu $menu): void
    {
        $this->em->remove($menu);
        $this->em->flush();
    }

    /**
     * Get all menus
     */
    public function getAllMenus(): array
    {
        return $this->menuRepository->findBy([], ['position' => 'ASC']);
    }

    /**
     * Initialize default menus if none exist
     */
    public function initializeDefaultMenus(): void
    {
        // Check if main menu exists
        $mainMenu = $this->menuRepository->findByLocation('main');
        if ($mainMenu) {
            return;
        }

        // Create main menu
        $mainMenu = $this->createMenu('Menu Principal', 'main');

        // Create default items based on current hardcoded menu
        // These will be created but admin can modify them later
    }
}
