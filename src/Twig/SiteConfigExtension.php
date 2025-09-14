<?php

namespace App\Twig;

use App\Service\SiteConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SiteConfigExtension extends AbstractExtension
{
    public function __construct(
        private SiteConfigService $siteConfigService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('site_config', [$this, 'getSiteConfig']),
            new TwigFunction('club_info', [$this, 'getClubInfo']),
        ];
    }

    public function getSiteConfig(string $key, ?string $default = null): ?string
    {
        return $this->siteConfigService->get($key, $default);
    }

    public function getClubInfo(): array
    {
        return $this->siteConfigService->getClubInfo();
    }
}