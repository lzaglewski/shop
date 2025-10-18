<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\CookieConsent\Model\CookieConsent;
use App\Domain\CookieConsent\Repository\CookieConsentRepositoryInterface;
use App\Domain\User\Model\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineCookieConsentRepository implements CookieConsentRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(CookieConsent::class);
    }

    public function findByUser(User $user): ?CookieConsent
    {
        return $this->repository->findOneBy(['user' => $user]);
    }

    public function save(CookieConsent $consent): void
    {
        $this->entityManager->persist($consent);
        $this->entityManager->flush();
    }
}
