<?php

namespace App\Repository;

use App\Entity\SiteConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteConfig>
 */
class SiteConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteConfig::class);
    }

    public function findByKey(string $key): ?SiteConfig
    {
        return $this->findOneBy(['configKey' => $key]);
    }

    public function getValue(string $key, ?string $default = null): ?string
    {
        $config = $this->findByKey($key);
        return $config ? $config->getConfigValue() : $default;
    }

    public function setValue(string $key, ?string $value, ?string $description = null): SiteConfig
    {
        $config = $this->findByKey($key) ?? new SiteConfig();
        $config->setConfigKey($key);
        $config->setConfigValue($value);
        
        if ($description !== null) {
            $config->setDescription($description);
        }

        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();

        return $config;
    }
}