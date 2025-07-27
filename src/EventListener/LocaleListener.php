<?php

namespace App\EventListener;

use App\Service\TranslationManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
class LocaleListener
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Priority order: URL parameter, session, default
        $locale = null;

        // 1. Check URL parameter
        if ($request->query->has('_locale')) {
            $locale = $request->query->get('_locale');
        }

        // 2. Check session
        if (!$locale && $request->hasSession()) {
            $locale = $request->getSession()->get('_locale');
        }

        // 3. Use default
        if (!$locale) {
            $locale = $this->translationManager->getDefaultLocale();
        }

        // Validate locale
        if (!$this->translationManager->isLocaleSupported($locale)) {
            $locale = $this->translationManager->getDefaultLocale();
        }

        // Set locale on request
        $request->setLocale($locale);

        // Store in session for persistence
        if ($request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }
    }
}