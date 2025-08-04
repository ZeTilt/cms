<?php

namespace App\EventListener;

use App\Service\EavService;
use App\Trait\HasDynamicAttributesTrait;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;

/**
 * Injecte automatiquement le service EAV dans les entités qui utilisent HasDynamicAttributesTrait
 */
class EavServiceInjectionListener implements EventSubscriber
{
    public function __construct(
        private EavService $eavService
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postLoad,
        ];
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();
        
        // Vérifier si l'entité utilise le trait EAV
        if ($this->entityUsesEavTrait($entity)) {
            // Injecter le service EAV
            $entity->setEavService($this->eavService);
        }
    }

    private function entityUsesEavTrait(object $entity): bool
    {
        $reflection = new \ReflectionClass($entity);
        $traits = $reflection->getTraitNames();
        
        return in_array(HasDynamicAttributesTrait::class, $traits);
    }
}