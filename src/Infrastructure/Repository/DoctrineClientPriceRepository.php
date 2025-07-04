<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Pricing\Repository\ClientPriceRepositoryInterface;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
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
    
    public function findOneBy(array $criteria): ?ClientPrice
    {
        return $this->repository->findOneBy($criteria);
    }

    public function remove(ClientPrice $clientPrice): void
    {
        $this->entityManager->remove($clientPrice);
        $this->entityManager->flush();
    }
}
