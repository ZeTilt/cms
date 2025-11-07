<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute les headers anti-cache sur toutes les réponses HTML
 * pour que la navigation (avec app.user) s'affiche toujours correctement
 */
class NoCacheSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Ne pas appliquer sur les sous-requêtes
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // Ne pas appliquer sur les assets statiques (images, CSS, JS, etc.)
        $path = $request->getPathInfo();
        if (preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|pdf)$/i', $path)) {
            return;
        }

        // Ne pas appliquer sur les routes d'assets Symfony
        if (str_starts_with($path, '/_')) {
            return;
        }

        // Ajouter les headers anti-cache pour toutes les pages HTML
        $contentType = $response->headers->get('Content-Type');
        if ($contentType && str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }
    }
}
