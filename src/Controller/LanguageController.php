<?php

namespace App\Controller;

use App\Service\TranslationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/language')]
class LanguageController extends AbstractController
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }

    #[Route('/switch/{locale}', name: 'app_language_switch')]
    public function switchLanguage(string $locale, Request $request, SessionInterface $session): Response
    {
        // Validate locale
        if (!$this->translationManager->isLocaleSupported($locale)) {
            throw $this->createNotFoundException('Locale not supported');
        }

        // Store locale in session
        $session->set('_locale', $locale);

        // Get redirect URL from referer or default to home
        $referer = $request->headers->get('referer');
        $redirectUrl = $referer && str_contains($referer, $request->getSchemeAndHttpHost()) 
            ? $referer 
            : $this->generateUrl('app_home');

        // Remove any existing locale parameter from URL
        $redirectUrl = preg_replace('/[?&]_locale=[^&]*/', '', $redirectUrl);
        
        // Add new locale parameter
        $separator = str_contains($redirectUrl, '?') ? '&' : '?';
        $redirectUrl .= $separator . '_locale=' . $locale;

        return $this->redirect($redirectUrl);
    }
}