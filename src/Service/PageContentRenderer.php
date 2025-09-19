<?php

namespace App\Service;

use App\Service\SiteConfigService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PageContentRenderer
{
    public function __construct(
        private SiteConfigService $siteConfigService,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function renderContent(string $content): string
    {
        // Create a simple Twig environment with an array loader
        $loader = new ArrayLoader(['content' => $content]);
        $twig = new Environment($loader);

        // Add site_config function
        $twig->addFunction(new \Twig\TwigFunction('site_config', function (string $key, ?string $default = null) {
            return $this->siteConfigService->get($key, $default);
        }));

        // Add path function for routing
        $twig->addFunction(new \Twig\TwigFunction('path', function (string $route, array $parameters = []) {
            return $this->urlGenerator->generate($route, $parameters);
        }));

        try {
            return $twig->render('content');
        } catch (\Exception $e) {
            // If there's an error in Twig rendering, return the original content
            return $content;
        }
    }
}