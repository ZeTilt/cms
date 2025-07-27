<?php

namespace App\Service;

use App\Entity\SystemSetting;
use Doctrine\ORM\EntityManagerInterface;

class SettingsManager
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get a setting value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->entityManager->getRepository(SystemSetting::class)->find($key);
        
        return $setting ? $setting->getParsedValue() : $default;
    }

    /**
     * Set a setting value
     */
    public function set(string $key, mixed $value, ?string $description = null): void
    {
        $setting = $this->entityManager->getRepository(SystemSetting::class)->find($key);
        
        if (!$setting) {
            $setting = new SystemSetting();
            $setting->setSettingKey($key);
            $this->entityManager->persist($setting);
        }

        $setting->setValue($value);
        
        if ($description !== null) {
            $setting->setDescription($description);
        }

        $this->entityManager->flush();
    }

    /**
     * Check if a setting exists
     */
    public function has(string $key): bool
    {
        $setting = $this->entityManager->getRepository(SystemSetting::class)->find($key);
        
        return $setting !== null;
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): void
    {
        $setting = $this->entityManager->getRepository(SystemSetting::class)->find($key);
        
        if ($setting) {
            $this->entityManager->remove($setting);
            $this->entityManager->flush();
        }
    }

    /**
     * Get all settings
     */
    public function all(): array
    {
        $settings = $this->entityManager->getRepository(SystemSetting::class)->findAll();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getParsedValue();
        }
        
        return $result;
    }

    /**
     * Get settings by prefix
     */
    public function getByPrefix(string $prefix): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('s')
           ->from(SystemSetting::class, 's')
           ->where('s.settingKey LIKE :prefix')
           ->setParameter('prefix', $prefix . '%');

        $settings = $qb->getQuery()->getResult();
        
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->getSettingKey()] = $setting->getParsedValue();
        }
        
        return $result;
    }
}