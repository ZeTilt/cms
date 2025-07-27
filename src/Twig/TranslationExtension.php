<?php

namespace App\Twig;

use App\Service\TranslationManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TranslationExtension extends AbstractExtension
{
    public function __construct(
        private TranslationManager $translationManager,
        private RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('translate', [$this, 'translate']),
            new TwigFunction('current_locale', [$this, 'getCurrentLocale']),
            new TwigFunction('supported_locales', [$this, 'getSupportedLocales']),
            new TwigFunction('locale_name', [$this, 'getLocaleName']),
            new TwigFunction('translation_completion', [$this, 'getTranslationCompletion']),
        ];
    }

    /**
     * Translate a field for an entity
     */
    public function translate(object $entity, string $fieldName, ?string $locale = null): ?string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        return $this->translationManager->getTranslation($entity, $fieldName, $locale);
    }

    /**
     * Get current locale from request
     */
    public function getCurrentLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getLocale() ?? $this->translationManager->getDefaultLocale();
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        return $this->translationManager->getSupportedLocales();
    }

    /**
     * Get locale display name
     */
    public function getLocaleName(string $locale): string
    {
        $names = $this->translationManager->getLocaleNames();
        return $names[$locale] ?? $locale;
    }

    /**
     * Get translation completion for entity
     */
    public function getTranslationCompletion(object $entity): array
    {
        return $this->translationManager->getTranslationCompletion($entity);
    }
}