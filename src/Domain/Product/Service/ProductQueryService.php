<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;

class ProductQueryService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
        private readonly ProductVisibilityService $productVisibilityService
    ) {
    }

    public function createFilteredProductQuery(
        ?User $user = null,
        ?string $categoryId = null,
        ?string $search = null,
        ?bool $activeOnly = true
    ): QueryBuilder {
        $queryBuilder = $activeOnly 
            ? $this->productRepository->createActiveProductsQueryBuilder()
            : $this->productRepository->createAllProductsQueryBuilder();

        if ($categoryId) {
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        if ($user && $this->productVisibilityService->shouldFilterForClient($user)) {
            $this->productRepository->addClientVisibilityFilter($queryBuilder, $user);
        }

        return $queryBuilder;
    }

    public function createAdminFilteredProductQuery(
        ?string $categoryId = null,
        ?string $search = null,
        ?string $status = null
    ): QueryBuilder {
        $queryBuilder = $this->productRepository->createAllProductsQueryBuilder();

        if ($categoryId) {
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        if ($status !== null && $status !== 'All' && $status !== '') {
            $this->productRepository->addStatusFilter($queryBuilder, $status === '1');
        }

        return $queryBuilder;
    }

    public function getPaginatedProducts(QueryBuilder $queryBuilder, int $page, int $limit): PaginationInterface
    {
        return $this->productRepository->getPaginatedProducts($queryBuilder, $page, $limit);
    }

    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }
}