<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Model\Pricing\ClientPrice;
use App\Domain\Model\Product\Product;
use App\Domain\Model\User\User;
use App\Domain\Repository\ClientPriceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineClientPriceRepository implements ClientPriceRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(ClientPrice::class);
    }

    public function save(ClientPrice $clientPrice): void
    {
        $this->entityManager->persist($clientPrice);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?ClientPrice
    {
        return $this->repository->find($id);
    }

    public function findByClientAndProduct(User $client, Product $product): ?ClientPrice
    {
        return $this->repository->findOneBy([
            'client' => $client,
            'product' => $product,
            'isActive' => true
        ]);
    }

    public function findByClient(User $client): array
    {
        return $this->repository->findBy([
            'client' => $client,
            'isActive' => true
        ]);
    }

    public function findByProduct(Product $product): array
    {
        return $this->repository->findBy([
            'product' => $product,
            'isActive' => true
        ]);
    }

    public function findAll(): array
    {
        return $this->repository->findBy([
            'isActive' => true
        ]);
    }
    
    public function remove(ClientPrice $clientPrice): void
    {
        $this->entityManager->remove($clientPrice);
        $this->entityManager->flush();
    }
}
