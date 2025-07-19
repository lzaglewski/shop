<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class DoctrineProductRepository implements ProductRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Product::class);
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function findById(int $id): ?Product
    {
        return $this->repository->find($id);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->repository->findOneBy(['sku' => $sku]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findActive(): array
    {
        return $this->repository->findBy(['isActive' => true]);
    }

    public function findByCategory(ProductCategory $category): array
    {
        return $this->repository->findBy(['category' => $category]);
    }

    public function remove(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    public function createActiveProductsQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC');

        return $queryBuilder;
    }

    public function createAllProductsQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('p')
            ->from(Product::class, 'p')
            ->orderBy('p.name', 'ASC');

        return $queryBuilder;
    }

    public function addCategoryFilter(QueryBuilder $queryBuilder, string $categoryId): QueryBuilder
    {
        if (!empty($categoryId)) {
            // Get the category and all its subcategories
            $category = $this->entityManager->getRepository(ProductCategory::class)->find($categoryId);
            
            if ($category) {
                // Get all subcategory IDs recursively
                $categoryIds = $this->getAllCategoryIds($category);
                
                $queryBuilder
                    ->andWhere('p.category IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            } else {
                // Fallback to original behavior if category not found
                $queryBuilder
                    ->andWhere('p.category.id = :categoryId')
                    ->setParameter('categoryId', $categoryId);
            }
        }

        return $queryBuilder;
    }
    
    /**
     * Get a category and all its subcategories as an array of IDs
     */
    private function getAllCategoryIds(ProductCategory $category): array
    {
        $ids = [$category->getId()];
        
        foreach ($category->getChildren() as $child) {
            $ids = array_merge($ids, $this->getAllCategoryIds($child));
        }
        
        return $ids;
    }

    public function addSearchFilter(QueryBuilder $queryBuilder, string $search): QueryBuilder
    {
        if (!empty($search)) {
            $queryBuilder
                ->andWhere('p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $queryBuilder;
    }

    public function addStatusFilter(QueryBuilder $queryBuilder, bool $isActive): QueryBuilder
    {
        $queryBuilder
            ->andWhere('p.isActive = :status')
            ->setParameter('status', $isActive);

        return $queryBuilder;
    }
}
