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
    
    public function findRootCategories(): array
    {
        return $this->repository->findBy(['parent' => null], ['name' => 'ASC']);
    }

    public function remove(ProductCategory $category): void
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function findByIdWithChildren(int $id): ?ProductCategory
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('c', 'children', 'grandchildren')
            ->from(ProductCategory::class, 'c')
            ->leftJoin('c.children', 'children')
            ->leftJoin('children.children', 'grandchildren')
            ->where('c.id = :id')
            ->setParameter('id', $id);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findAllWithRelations(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('c', 'parent', 'children')
            ->from(ProductCategory::class, 'c')
            ->leftJoin('c.parent', 'parent')
            ->leftJoin('c.children', 'children')
            ->orderBy('c.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
