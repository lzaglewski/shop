<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Settings\Model\Settings;
use App\Domain\Settings\Repository\SettingsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineSettingsRepository extends ServiceEntityRepository implements SettingsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    public function findByKey(string $key): ?Settings
    {
        return $this->findOneBy(['settingKey' => $key]);
    }

    public function save(Settings $settings): void
    {
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();
    }

    public function findAll(): array
    {
        return parent::findAll();
    }
}