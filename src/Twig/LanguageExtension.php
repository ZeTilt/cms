<?php

namespace App\Twig;

use App\Service\TranslationManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LanguageExtension extends AbstractExtension
{
    public function __construct(
        private TranslationManager $translationManager,
        private RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_supported_locales', [$this, 'getSupportedLocales']),
            new TwigFunction('get_current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('should_show_language_selector', [$this, 'shouldShowLanguageSelector']),
            new TwigFunction('get_locale_flag', [$this, 'getLocaleFlag']),
            new TwigFunction('get_locale_name', [$this, 'getLocaleName']),
            new TwigFunction('generate_language_switch_url', [$this, 'generateLanguageSwitchUrl']),
        ];
    }

    public function getSupportedLocales(): array
    {
        return $this->translationManager->getSupportedLocales();
    }

    public function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getLocale() : $this->translationManager->getDefaultLocale();
    }

    public function shouldShowLanguageSelector(): bool
    {
        $supportedLocales = $this->translationManager->getSupportedLocales();
        return count($supportedLocales) > 1;
    }

    public function getLocaleFlag(string $locale): string
    {
        $flags = [
            'en' => '🇺🇸',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'nl' => '🇳🇱',
            'ru' => '🇷🇺',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
        ];

        return $flags[$locale] ?? '🌐';
    }

    public function getLocaleName(string $locale): string
    {
        $names = $this->translationManager->getLocaleNames();
        return $names[$locale] ?? $locale;
    }

    public function generateLanguageSwitchUrl(string $locale): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return '/';
        }

        // Get current route and parameters
        $route = $request->attributes->get('_route');
        $routeParams = $request->attributes->get('_route_params', []);
        $queryParams = $request->query->all();

        // Add locale to query parameters
        $queryParams['_locale'] = $locale;

        // Build URL
        $url = $request->getPathInfo();
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}