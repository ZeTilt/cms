<?php

namespace App\Service;

use App\Repository\SiteConfigRepository;

class SiteConfigService
{
    public function __construct(
        private SiteConfigRepository $siteConfigRepository
    ) {
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->siteConfigRepository->getValue($key, $default);
    }

    public function set(string $key, ?string $value, ?string $description = null): void
    {
        $this->siteConfigRepository->setValue($key, $value, $description);
    }

    public function getClubInfo(): array
    {
        return [
            'name' => $this->get('club_name', 'Club Subaquatique des Vénètes'),
            'address' => $this->get('club_address', '5 Av. du Président Wilson, 56000 Vannes'),
            'phone' => $this->get('club_phone', '02 97 XX XX XX'),
            'email' => $this->get('club_email', 'contact@plongee-venetes.fr'),
            'facebook' => $this->get('club_facebook', 'https://www.facebook.com/plongeevenetes/')
        ];
    }
}