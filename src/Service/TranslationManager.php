<?php

namespace App\Service;

use App\Entity\Translation;
use Doctrine\ORM\EntityManagerInterface;

class TranslationManager
{
    private ?array $supportedLocales = null;
    private ?string $defaultLocale = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager
    ) {
    }

    /**
     * Get translation for a specific entity field
     */
    public function getTranslation(object $entity, string $fieldName, string $locale): ?string
    {
        if ($locale === $this->getDefaultLocale()) {
            // For default locale, return original value
            $getter = 'get' . ucfirst($fieldName);
            if (method_exists($entity, $getter)) {
                return $entity->$getter();
            }
            return null;
        }

        $translation = $this->entityManager->getRepository(Translation::class)->findOneBy([
            'entityType' => $this->getEntityType($entity),
            'entityId' => $entity->getId(),
            'fieldName' => $fieldName,
            'locale' => $locale,
        ]);

        return $translation?->getValue();
    }

    /**
     * Set translation for a specific entity field
     */
    public function setTranslation(object $entity, string $fieldName, string $locale, string $value): void
    {
        if ($locale === $this->getDefaultLocale()) {
            // For default locale, update entity directly
            $setter = 'set' . ucfirst($fieldName);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
                $this->entityManager->flush();
            }
            return;
        }

        $translation = $this->entityManager->getRepository(Translation::class)->findOneBy([
            'entityType' => $this->getEntityType($entity),
            'entityId' => $entity->getId(),
            'fieldName' => $fieldName,
            'locale' => $locale,
        ]);

        if (!$translation) {
            $translation = new Translation();
            $translation->setEntityType($this->getEntityType($entity));
            $translation->setEntityId($entity->getId());
            $translation->setFieldName($fieldName);
            $translation->setLocale($locale);
            $this->entityManager->persist($translation);
        }

        $translation->setValue($value);
        $this->entityManager->flush();
    }

    /**
     * Get all translations for an entity
     */
    public function getEntityTranslations(object $entity): array
    {
        $translations = $this->entityManager->getRepository(Translation::class)->findBy([
            'entityType' => $this->getEntityType($entity),
            'entityId' => $entity->getId(),
        ]);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->getLocale()][$translation->getFieldName()] = $translation->getValue();
        }

        return $result;
    }

    /**
     * Get translatable fields for an entity
     */
    public function getTranslatableFields(object $entity): array
    {
        $entityType = $this->getEntityType($entity);
        
        return match ($entityType) {
            'Page' => ['title', 'excerpt', 'metaDescription'],
            'Article' => ['title', 'content', 'excerpt', 'metaDescription'],
            'Event' => ['title', 'description', 'location'],
            'Service' => ['name', 'description', 'features'],
            'Testimonial' => ['content', 'clientName', 'clientCompany'],
            default => [],
        };
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        if ($this->supportedLocales === null) {
            $this->supportedLocales = $this->settingsManager->get('translation.supported_locales', ['en', 'fr']);
        }
        return $this->supportedLocales;
    }

    /**
     * Get default locale
     */
    public function getDefaultLocale(): string
    {
        if ($this->defaultLocale === null) {
            $this->defaultLocale = $this->settingsManager->get('translation.default_locale', 'en');
        }
        return $this->defaultLocale;
    }

    /**
     * Set supported locales
     */
    public function setSupportedLocales(array $locales): void
    {
        $this->supportedLocales = $locales;
        $this->settingsManager->set('translation.supported_locales', $locales, 'Langues supportées par le système de traduction');
    }

    /**
     * Set default locale
     */
    public function setDefaultLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
        $this->settingsManager->set('translation.default_locale', $locale, 'Langue par défaut du site');
    }

    /**
     * Check if locale is supported
     */
    public function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales());
    }

    /**
     * Get entity type name
     */
    private function getEntityType(object $entity): string
    {
        $className = get_class($entity);
        return substr($className, strrpos($className, '\\') + 1);
    }

    /**
     * Get locale names for admin interface
     */
    public function getLocaleNames(): array
    {
        return [
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
            'ru' => 'Русский',
            'zh' => '中文',
            'ja' => '日本語',
        ];
    }

    /**
     * Delete all translations for an entity
     */
    public function deleteEntityTranslations(object $entity): void
    {
        $translations = $this->entityManager->getRepository(Translation::class)->findBy([
            'entityType' => $this->getEntityType($entity),
            'entityId' => $entity->getId(),
        ]);

        foreach ($translations as $translation) {
            $this->entityManager->remove($translation);
        }

        $this->entityManager->flush();
    }

    /**
     * Get translation completion percentage for an entity
     */
    public function getTranslationCompletion(object $entity): array
    {
        $translatableFields = $this->getTranslatableFields($entity);
        $translations = $this->getEntityTranslations($entity);
        
        $completion = [];
        foreach ($this->getSupportedLocales() as $locale) {
            if ($locale === $this->getDefaultLocale()) {
                $completion[$locale] = 100; // Default locale is always complete
                continue;
            }
            
            $translatedFields = count($translations[$locale] ?? []);
            $totalFields = count($translatableFields);
            $completion[$locale] = $totalFields > 0 ? round(($translatedFields / $totalFields) * 100) : 0;
        }
        
        return $completion;
    }
}