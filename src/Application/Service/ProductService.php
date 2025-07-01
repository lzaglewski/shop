<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Doctrine\ORM\QueryBuilder;

class ProductService
{
    private ProductRepositoryInterface $productRepository;
    private ProductCategoryRepositoryInterface $categoryRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductCategoryRepositoryInterface $categoryRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function createProduct(
        string $name,
        string $sku,
        string $description,
        float $basePrice,
        int $stock,
        ?ProductCategory $category = null,
        ?string $imageFilename = null
    ): Product {
        $product = new Product(
            $name,
            $sku,
            $description,
            $basePrice,
            $stock,
            $category,
            $imageFilename
        );

        $this->productRepository->save($product);

        return $product;
    }

    public function updateProduct(
        Product $product,
        string $name,
        string $description,
        float $basePrice,
        int $stock,
        ?ProductCategory $category = null,
        ?string $imageFilename = null
    ): Product {
        $product->setName($name);
        $product->setDescription($description);
        $product->setBasePrice($basePrice);
        $product->setStock($stock);
        $product->setCategory($category);

        if ($imageFilename !== null) {
            $product->setImageFilename($imageFilename);
        }

        $this->productRepository->save($product);

        return $product;
    }

    public function activateProduct(Product $product): void
    {
        $product->setIsActive(true);
        $this->productRepository->save($product);
    }

    public function deactivateProduct(Product $product): void
    {
        $product->setIsActive(false);
        $this->productRepository->save($product);
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function getProductBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    public function getAllProducts(): array
    {
        return $this->productRepository->findAll();
    }

    public function getActiveProducts(): array
    {
        return $this->productRepository->findActive();
    }

    public function getProductsByCategory(ProductCategory $category): array
    {
        return $this->productRepository->findByCategory($category);
    }

    public function getActiveProductsQuery(): QueryBuilder
    {
        return $this->productRepository->createActiveProductsQueryBuilder();
    }

    public function filterByCategory(QueryBuilder $queryBuilder, string $categoryId): QueryBuilder
    {
        return $this->productRepository->addCategoryFilter($queryBuilder, $categoryId);
    }

    public function filterBySearch(QueryBuilder $queryBuilder, string $search): QueryBuilder
    {
        return $this->productRepository->addSearchFilter($queryBuilder, $search);
    }

    public function getAllCategories(): array
    {
        return $this->categoryRepository->findAll();
    }

    public function saveProduct(Product $product): void
    {
        $this->productRepository->save($product);
    }
}
