<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Product\ProductCategory;

interface ProductCategoryRepositoryInterface
{
    public function save(ProductCategory $category): void;
    
    public function findById(int $id): ?ProductCategory;
    
    public function findByName(string $name): ?ProductCategory;
    
    public function findAll(): array;
    
    public function remove(ProductCategory $category): void;
}
