<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Service\ProductQueryService;
use App\Domain\User\Model\User;
use App\Application\Pricing\ClientPriceService;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;

class ProductApplicationService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductQueryService $productQueryService,
        private readonly ClientPriceService $clientPriceService
    ) {
    }

    public function getFilteredProducts(
        ?User $user,
        ?string $categoryId,
        ?string $search,
        int $page,
        int $limit,
        bool $activeOnly = true,
        ?string $sortBy = null,
        ?string $sortOrder = null
    ): PaginationInterface {
        $queryBuilder = $this->productQueryService->createFilteredProductQuery(
            $user,
            $categoryId,
            $search,
            $activeOnly,
            $sortBy,
            $sortOrder
        );

        return $this->productQueryService->getPaginatedProducts($queryBuilder, $page, $limit);
    }

    public function getAdminFilteredProducts(
        ?string $categoryId,
        ?string $search,
        ?string $status,
        int $page,
        int $limit
    ): PaginationInterface {
        $queryBuilder = $this->productQueryService->createAdminFilteredProductQuery(
            $categoryId,
            $search,
            $status
        );

        return $this->productQueryService->getPaginatedProducts($queryBuilder, $page, $limit);
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function getProductWithClientAccess(int $id, User $user): ?Product
    {
        $product = $this->getProductById($id);

        if (!$product) {
            return null;
        }

        // Check if the client has access to this product
        if (!$this->clientPriceService->isProductVisibleToClient($product, $user)) {
            return null;
        }

        return $product;
    }

    public function saveProduct(Product $product): void
    {
        $this->productRepository->save($product);
    }

    public function deleteProduct(Product $product): void
    {
        if ($product->isActive()) {
            // Soft delete for active products
            $product->setIsActive(false);
            $this->productRepository->save($product);
        } else {
            // Hard delete for inactive products
            $this->productRepository->remove($product);
        }
    }

    public function getAllCategories(): array
    {
        return $this->productQueryService->getAllCategories();
    }

    public function getCategoriesWithVisibleProducts(?User $user = null): array
    {
        return $this->productQueryService->getCategoriesWithVisibleProducts($user);
    }
}
