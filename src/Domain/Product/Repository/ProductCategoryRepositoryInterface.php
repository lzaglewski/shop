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
}
