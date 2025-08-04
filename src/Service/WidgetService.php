<?php

namespace App\Service;

use App\Entity\Widget;
use App\Repository\WidgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class WidgetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private WidgetRepository $widgetRepository,
        private CacheItemPoolInterface $cache,
        private Environment $twig,
        private LoggerInterface $logger,
        private bool $widgetCacheEnabled = true
    ) {}

    /**
     * Rend un widget par son ID
     */
    public function renderWidget(int $widgetId, array $parameters = []): string
    {
        $widget = $this->widgetRepository->find($widgetId);
        
        if (!$widget || !$widget->isActive()) {
            return '';
        }

        return $this->renderWidgetEntity($widget, $parameters);
    }

    /**
     * Rend un widget par son nom
     */
    public function renderWidgetByName(string $name, array $parameters = []): string
    {
        $widget = $this->widgetRepository->findActiveByName($name);
        
        if (!$widget) {
            return '';
        }

        return $this->renderWidgetEntity($widget, $parameters);
    }

    /**
     * Rend une entité widget
     */
    public function renderWidgetEntity(Widget $widget, array $parameters = []): string
    {
        try {
            // Vérifier le cache si activé
            if ($this->widgetCacheEnabled && $widget->isCacheable()) {
                $cacheKey = $this->getCacheKey($widget, $parameters);
                $cacheItem = $this->cache->getItem($cacheKey);
                
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                }
            }

            // Rendre le widget selon son type
            $content = match($widget->getType()) {
                'html' => $this->renderHtmlWidget($widget, $parameters),
                'iframe' => $this->renderIframeWidget($widget, $parameters),
                'script' => $this->renderScriptWidget($widget, $parameters),
                'weather' => $this->renderWeatherWidget($widget, $parameters),
                'map' => $this->renderMapWidget($widget, $parameters),
                'social' => $this->renderSocialWidget($widget, $parameters),
                'calendar' => $this->renderCalendarWidget($widget, $parameters),
                'custom' => $this->renderCustomWidget($widget, $parameters),
                default => $this->renderDefaultWidget($widget, $parameters)
            };

            // Mettre en cache si activé
            if ($this->widgetCacheEnabled && $widget->isCacheable() && isset($cacheItem)) {
                $cacheItem->set($content);
                $cacheItem->expiresAfter($widget->getCacheTime());
                $this->cache->save($cacheItem);
            }

            return $content;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du rendu du widget', [
                'widget_id' => $widget->getId(),
                'widget_name' => $widget->getName(),
                'error' => $e->getMessage()
            ]);
            
            return '';
        }
    }

    /**
     * Rend un widget HTML personnalisé
     */
    private function renderHtmlWidget(Widget $widget, array $parameters): string
    {
        $content = $widget->getContent();
        
        // Remplacer les paramètres dans le contenu
        foreach ($parameters as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }

    /**
     * Rend un widget iframe
     */
    private function renderIframeWidget(Widget $widget, array $parameters): string
    {
        $settings = $widget->getSettings() ?? [];
        $src = $widget->getContent();
        
        $width = $settings['width'] ?? '100%';
        $height = $settings['height'] ?? '400';
        $frameborder = $settings['frameborder'] ?? '0';
        $scrolling = $settings['scrolling'] ?? 'no';
        
        return sprintf(
            '<iframe src="%s" width="%s" height="%s" frameborder="%s" scrolling="%s"></iframe>',
            htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($width, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($height, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($frameborder, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($scrolling, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Rend un widget script
     */
    private function renderScriptWidget(Widget $widget, array $parameters): string
    {
        $settings = $widget->getSettings() ?? [];
        $script = $widget->getContent();
        
        $html = '';
        
        // Ajouter un container si spécifié
        if (isset($settings['container_id'])) {
            $html .= sprintf('<div id="%s"></div>', htmlspecialchars($settings['container_id'], ENT_QUOTES, 'UTF-8'));
        }
        
        // Ajouter le script
        $html .= sprintf('<script>%s</script>', $script);
        
        return $html;
    }

    /**
     * Rend un widget météo (exemple avec SHOM)
     */
    private function renderWeatherWidget(Widget $widget, array $parameters): string
    {
        $settings = $widget->getSettings() ?? [];
        $location = $settings['location'] ?? 'PORT-NAVALO';
        $locale = $settings['locale'] ?? 'fr';
        $size = $settings['size'] ?? 'petite';
        
        $scriptSrc = sprintf(
            'https://services.data.shom.fr/hdm/vignette/%s/%s?locale=%s',
            $size,
            $location,
            $locale
        );
        
        $iframeId = 'vignette_shom_' . uniqid();
        $width = $settings['width'] ?? '162';
        $height = $settings['height'] ?? '350';
        
        return sprintf(
            '<div class="textwidget weather-widget">
                <script src="%s"></script>
                <iframe width="%s" id="%s" height="%s" frameborder="0" scrolling="no"></iframe>
            </div>',
            $scriptSrc,
            $width,
            $iframeId,
            $height
        );
    }

    /**
     * Rend un widget carte
     */
    private function renderMapWidget(Widget $widget, array $parameters): string
    {
        // Implémentation pour cartes (OpenStreetMap, Google Maps, etc.)
        return '<div class="map-widget">Carte à implémenter</div>';
    }

    /**
     * Rend un widget social
     */
    private function renderSocialWidget(Widget $widget, array $parameters): string
    {
        // Implémentation pour réseaux sociaux
        return '<div class="social-widget">Widget social à implémenter</div>';
    }

    /**
     * Rend un widget calendrier
     */
    private function renderCalendarWidget(Widget $widget, array $parameters): string
    {
        // Implémentation pour calendrier
        return '<div class="calendar-widget">Calendrier à implémenter</div>';
    }

    /**
     * Rend un widget personnalisé
     */
    private function renderCustomWidget(Widget $widget, array $parameters): string
    {
        try {
            return $this->twig->render('widgets/' . $widget->getName() . '.html.twig', array_merge([
                'widget' => $widget,
                'settings' => $widget->getSettings()
            ], $parameters));
        } catch (\Exception $e) {
            return $this->renderDefaultWidget($widget, $parameters);
        }
    }

    /**
     * Rend un widget par défaut
     */
    private function renderDefaultWidget(Widget $widget, array $parameters): string
    {
        return $this->renderHtmlWidget($widget, $parameters);
    }

    /**
     * Génère une clé de cache pour un widget
     */
    private function getCacheKey(Widget $widget, array $parameters): string
    {
        return sprintf(
            'widget_%d_%s',
            $widget->getId(),
            md5(serialize($parameters))
        );
    }

    /**
     * Vide le cache d'un widget
     */
    public function clearWidgetCache(Widget $widget): void
    {
        if (!$this->widgetCacheEnabled) {
            return;
        }

        // Supprimer toutes les entrées de cache pour ce widget
        $pattern = sprintf('widget_%d_*', $widget->getId());
        $this->cache->deleteItems([$pattern]);
    }

    /**
     * Traite les shortcodes de widgets dans un contenu
     */
    public function processShortcodes(string $content): string
    {
        // Traiter [widget id="123"]
        $content = preg_replace_callback(
            '/\[widget\s+id=["\'](\d+)["\']\]/i',
            function ($matches) {
                return $this->renderWidget((int) $matches[1]);
            },
            $content
        );

        // Traiter [widget name="widget-name"]
        $content = preg_replace_callback(
            '/\[widget\s+name=["\']([^"\']+)["\']\]/i',
            function ($matches) {
                return $this->renderWidgetByName($matches[1]);
            },
            $content
        );

        return $content;
    }

    /**
     * Valide le contenu d'un widget pour la sécurité
     */
    public function validateWidgetContent(Widget $widget): array
    {
        $errors = [];
        $content = $widget->getContent();

        // Vérifications de sécurité de base
        if ($widget->getType() !== 'script') {
            if (stripos($content, '<script') !== false) {
                $errors[] = 'Les balises <script> ne sont autorisées que pour les widgets de type "script"';
            }
        }

        // Vérifier les URLs externes
        if (preg_match_all('/src=["\']([^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = "URL invalide détectée : {$url}";
                }
            }
        }

        return $errors;
    }
}