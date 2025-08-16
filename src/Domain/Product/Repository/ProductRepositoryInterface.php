<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\User\Model\User;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;

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

    public function createAllProductsQueryBuilder(): QueryBuilder;

    public function addCategoryFilter(QueryBuilder $queryBuilder, array $categoryIds): QueryBuilder;

    public function addSearchFilter(QueryBuilder $queryBuilder, string $search): QueryBuilder;

    public function addStatusFilter(QueryBuilder $queryBuilder, bool $isActive): QueryBuilder;

    public function addClientVisibilityFilter(QueryBuilder $queryBuilder, User $client): QueryBuilder;


    public function getPaginatedProducts(QueryBuilder $queryBuilder, int $page, int $limit): PaginationInterface;
}
