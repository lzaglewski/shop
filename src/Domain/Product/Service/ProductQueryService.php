<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use App\Domain\User\Model\User;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;

class ProductQueryService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCategoryRepositoryInterface $categoryRepository,
        private readonly ProductVisibilityService $productVisibilityService,
        private readonly CategoryTreeService $categoryTreeService
    ) {
    }

    public function createFilteredProductQuery(
        ?User $user = null,
        ?string $categoryId = null,
        ?string $search = null,
        ?bool $activeOnly = true,
        ?string $sortBy = null,
        ?string $sortOrder = null
    ): QueryBuilder {
        $queryBuilder = $activeOnly
            ? $this->productRepository->createActiveProductsQueryBuilder()
            : $this->productRepository->createAllProductsQueryBuilder();

        if ($categoryId) {
            $categoryIds = $this->categoryTreeService->getAllSubcategoryIds((int)$categoryId);
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryIds);
        }

        if ($search) {
            $this->productRepository->addSearchFilter($queryBuilder, $search);
        }

        if ($user && $this->productVisibilityService->shouldFilterForClient($user)) {
            $this->productRepository->addClientVisibilityFilter($queryBuilder, $user);
        }

        if ($sortBy && $sortOrder) {
            $this->productRepository->addSorting($queryBuilder, $sortBy, $sortOrder);
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
            $categoryIds = $this->categoryTreeService->getAllSubcategoryIds((int)$categoryId);
            $this->productRepository->addCategoryFilter($queryBuilder, $categoryIds);
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

    public function getCategoriesWithVisibleProducts(?User $user = null): array
    {
        if (!$user || !$this->productVisibilityService->shouldFilterForClient($user)) {
            return $this->getAllCategories();
        }

        return $this->categoryRepository->findCategoriesWithVisibleProducts($user);
    }
}
