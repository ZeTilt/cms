<?php

namespace App\Controller;

use App\Entity\Page;
use App\Entity\Article;
use App\Entity\Event;
use App\Entity\Service;
use App\Entity\Testimonial;
use App\Service\TranslationManager;
use App\Service\ModuleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/translation')]
#[IsGranted('ROLE_ADMIN')]
class TranslationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslationManager $translationManager,
        private ModuleManager $moduleManager
    ) {
    }

    #[Route('', name: 'admin_translation_dashboard')]
    public function dashboard(): Response
    {
        // Get translatable entities
        $entities = [
            'Pages' => $this->entityManager->getRepository(Page::class)->findAll(),
            'Articles' => $this->entityManager->getRepository(Article::class)->findAll(),
        ];

        // Add other entities if modules are active
        if ($this->moduleManager->isModuleActive('events')) {
            $entities['Events'] = $this->entityManager->getRepository(Event::class)->findAll();
        }

        if ($this->moduleManager->isModuleActive('services')) {
            $entities['Services'] = $this->entityManager->getRepository(Service::class)->findAll();
        }

        if ($this->moduleManager->isModuleActive('testimonials')) {
            $entities['Testimonials'] = $this->entityManager->getRepository(Testimonial::class)->findAll();
        }

        return $this->render('admin/translation/dashboard.html.twig', [
            'entities' => $entities,
            'supportedLocales' => $this->translationManager->getSupportedLocales(),
            'defaultLocale' => $this->translationManager->getDefaultLocale(),
        ]);
    }

    #[Route('/edit/{entityType}/{id}', name: 'admin_translation_edit')]
    public function edit(string $entityType, int $id): Response
    {
        $entity = $this->getEntityByTypeAndId($entityType, $id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        $translatableFields = $this->translationManager->getTranslatableFields($entity);
        $existingTranslations = $this->translationManager->getEntityTranslations($entity);
        
        return $this->render('admin/translation/edit.html.twig', [
            'entity' => $entity,
            'entityType' => $entityType,
            'translatableFields' => $translatableFields,
            'existingTranslations' => $existingTranslations,
            'supportedLocales' => $this->translationManager->getSupportedLocales(),
            'defaultLocale' => $this->translationManager->getDefaultLocale(),
        ]);
    }

    #[Route('/save/{entityType}/{id}', name: 'admin_translation_save', methods: ['POST'])]
    public function save(string $entityType, int $id, Request $request): JsonResponse
    {
        $entity = $this->getEntityByTypeAndId($entityType, $id);
        
        if (!$entity) {
            return new JsonResponse(['success' => false, 'message' => 'Entité non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['locale']) || !isset($data['translations'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        $locale = $data['locale'];
        $translations = $data['translations'];

        if (!$this->translationManager->isLocaleSupported($locale)) {
            return new JsonResponse(['success' => false, 'message' => 'Langue non supportée'], 400);
        }

        try {
            foreach ($translations as $fieldName => $value) {
                if (trim($value) !== '') {
                    $this->translationManager->setTranslation($entity, $fieldName, $locale, $value);
                }
            }

            return new JsonResponse(['success' => true, 'message' => 'Traductions enregistrées avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'enregistrement des traductions: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/settings', name: 'admin_translation_settings')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function settings(): Response
    {
        return $this->render('admin/translation/settings.html.twig', [
            'supportedLocales' => $this->translationManager->getSupportedLocales(),
            'defaultLocale' => $this->translationManager->getDefaultLocale(),
            'availableLocales' => $this->translationManager->getLocaleNames(),
        ]);
    }

    #[Route('/settings/save', name: 'admin_translation_settings_save', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function saveSettings(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['supported_locales']) || !isset($data['default_locale'])) {
            return new JsonResponse(['success' => false, 'message' => 'Données invalides'], 400);
        }

        try {
            $supportedLocales = $data['supported_locales'];
            $defaultLocale = $data['default_locale'];

            // Validate that default locale is in supported locales
            if (!in_array($defaultLocale, $supportedLocales)) {
                return new JsonResponse(['success' => false, 'message' => 'La langue par défaut doit être dans les langues supportées'], 400);
            }

            $this->translationManager->setSupportedLocales($supportedLocales);
            $this->translationManager->setDefaultLocale($defaultLocale);

            return new JsonResponse(['success' => true, 'message' => 'Paramètres enregistrés avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'enregistrement des paramètres: ' . $e->getMessage()], 500);
        }
    }

    private function getEntityByTypeAndId(string $entityType, int $id): ?object
    {
        return match (ucfirst($entityType)) {
            'Page' => $this->entityManager->getRepository(Page::class)->find($id),
            'Article' => $this->entityManager->getRepository(Article::class)->find($id),
            'Event' => $this->entityManager->getRepository(Event::class)->find($id),
            'Service' => $this->entityManager->getRepository(Service::class)->find($id),
            'Testimonial' => $this->entityManager->getRepository(Testimonial::class)->find($id),
            default => null,
        };
    }
}