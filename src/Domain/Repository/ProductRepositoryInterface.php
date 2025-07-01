<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Product\Product;
use App\Domain\Model\Product\ProductCategory;
use Doctrine\ORM\QueryBuilder;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    
    public function findById(int $id): ?Product;
    
    public function findBySku(string $sku): ?Product;
    
    public function findAll(): array;
    
    public function findActive(): array;
    
    public function findByCategory(ProductCategory $category): array;
    
    public function remove(Product $product): void;
    
    public function createActiveProductsQueryBuilder(): QueryBuilder;
    
    public function addCategoryFilter(QueryBuilder $queryBuilder, string $categoryId): QueryBuilder;
    
    public function addSearchFilter(QueryBuilder $queryBuilder, string $search): QueryBuilder;
}
