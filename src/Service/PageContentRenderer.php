<?php

namespace App\Service;

use App\Service\SiteConfigService;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class PageContentRenderer
{
    public function __construct(
        private SiteConfigService $siteConfigService
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

        try {
            return $twig->render('content');
        } catch (\Exception $e) {
            // If there's an error in Twig rendering, return the original content
            return $content;
        }
    }
}