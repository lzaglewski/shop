<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

use App\Domain\Product\Model\ProductCategory;

interface ProductCategoryRepositoryInterface
{
    public function save(ProductCategory $category): void;

    public function findById(int $id): ?ProductCategory;

    public function findByName(string $name): ?ProductCategory;

    public function findAll(): array;
    
    public function findRootCategories(): array;

    public function remove(ProductCategory $category): void;

    /**
     * Find category by ID with all children eagerly loaded
     */
    public function findByIdWithChildren(int $id): ?ProductCategory;

    /**
     * Find all categories with parent/children relations eagerly loaded
     */
    public function findAllWithRelations(): array;
}
