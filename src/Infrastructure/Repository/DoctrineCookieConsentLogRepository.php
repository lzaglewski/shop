<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\CookieConsent\Model\CookieConsentLog;
use App\Domain\CookieConsent\Repository\CookieConsentLogRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCookieConsentLogRepository implements CookieConsentLogRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(CookieConsentLog $log): void
    {
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
