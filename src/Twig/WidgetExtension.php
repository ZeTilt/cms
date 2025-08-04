<?php

namespace App\Twig;

use App\Service\WidgetService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class WidgetExtension extends AbstractExtension
{
    public function __construct(
        private WidgetService $widgetService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('widget', [$this, 'renderWidget'], ['is_safe' => ['html']]),
            new TwigFunction('widget_by_name', [$this, 'renderWidgetByName'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('process_widgets', [$this, 'processWidgetShortcodes'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Rend un widget par son ID
     */
    public function renderWidget(int $widgetId, array $parameters = []): string
    {
        return $this->widgetService->renderWidget($widgetId, $parameters);
    }

    /**
     * Rend un widget par son nom
     */
    public function renderWidgetByName(string $name, array $parameters = []): string
    {
        return $this->widgetService->renderWidgetByName($name, $parameters);
    }

    /**
     * Traite les shortcodes de widgets dans un contenu
     */
    public function processWidgetShortcodes(string $content): string
    {
        return $this->widgetService->processShortcodes($content);
    }
}