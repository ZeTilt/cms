<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VapidExtension extends AbstractExtension
{
    public function __construct(
        private string $vapidPublicKey
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vapid_public_key', [$this, 'getVapidPublicKey']),
        ];
    }

    public function getVapidPublicKey(): string
    {
        return $this->vapidPublicKey;
    }
}
