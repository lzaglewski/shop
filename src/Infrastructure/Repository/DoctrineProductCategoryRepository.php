<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Product\Model\ProductCategory;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(ProductCategory::class);
    }

    public function save(ProductCategory $category): void
    {
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?ProductCategory
    {
        return $this->repository->find($id);
    }

    public function findByName(string $name): ?ProductCategory
    {
        return $this->repository->findOneBy(['name' => $name]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function remove(ProductCategory $category): void
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
