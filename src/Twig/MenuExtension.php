<?php

namespace App\Twig;

use App\Entity\MenuItem;
use App\Service\MenuManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private MenuManager $menuManager,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu_items', [$this, 'getMenuItems']),
            new TwigFunction('menu_url', [$this, 'getMenuItemUrl']),
        ];
    }

    /**
     * Get menu items for a location
     */
    public function getMenuItems(string $location): array
    {
        return $this->menuManager->getMenuItems($location);
    }

    /**
     * Get the URL for a menu item
     */
    public function getMenuItemUrl(MenuItem $item): string
    {
        return match ($item->getType()) {
            MenuItem::TYPE_ROUTE => $this->urlGenerator->generate(
                $item->getRoute(),
                $item->getRouteParams() ?? []
            ),
            MenuItem::TYPE_PAGE => $this->urlGenerator->generate(
                'public_page_show',
                ['slug' => $item->getPage()?->getSlug() ?? '']
            ),
            MenuItem::TYPE_URL => $item->getCustomUrl() ?? '#',
            MenuItem::TYPE_DROPDOWN => '#',
            default => '#',
        };
    }
}
