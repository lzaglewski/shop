<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\Product\Model\ProductCategory;
use App\Domain\Product\Repository\ProductCategoryRepositoryInterface;

class CategoryTreeService
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Get all subcategory IDs for a given category (including the category itself)
     * Uses efficient single query with eager loading
     */
    public function getAllSubcategoryIds(int $categoryId): array
    {
        $category = $this->categoryRepository->findByIdWithChildren($categoryId);
        
        if (!$category) {
            return [];
        }

        return $this->collectCategoryIds($category);
    }

    /**
     * Get all subcategory IDs for multiple categories
     */
    public function getAllSubcategoryIdsForCategories(array $categoryIds): array
    {
        $allIds = [];
        
        foreach ($categoryIds as $categoryId) {
            $ids = $this->getAllSubcategoryIds($categoryId);
            $allIds = array_merge($allIds, $ids);
        }

        return array_unique($allIds);
    }

    /**
     * Check if a category has any subcategories
     */
    public function hasSubcategories(int $categoryId): bool
    {
        $category = $this->categoryRepository->findByIdWithChildren($categoryId);
        
        return $category && $category->getChildren()->count() > 0;
    }

    /**
     * Get the full category hierarchy as a tree structure
     * Useful for building navigation menus
     */
    public function getCategoryTree(): array
    {
        $allCategories = $this->categoryRepository->findAllWithRelations();
        
        // Group by parent ID
        $categoriesByParent = [];
        foreach ($allCategories as $category) {
            $parentId = $category->getParent() ? $category->getParent()->getId() : null;
            $categoriesByParent[$parentId][] = $category;
        }

        // Build tree starting from root categories (parent = null)
        return $this->buildCategoryTree($categoriesByParent, null);
    }

    /**
     * Recursively collect all category IDs from a category and its children
     */
    private function collectCategoryIds(ProductCategory $category): array
    {
        $ids = [$category->getId()];
        
        foreach ($category->getChildren() as $child) {
            $ids = array_merge($ids, $this->collectCategoryIds($child));
        }
        
        return $ids;
    }

    /**
     * Recursively build category tree structure
     */
    private function buildCategoryTree(array $categoriesByParent, ?int $parentId): array
    {
        $tree = [];
        
        if (!isset($categoriesByParent[$parentId])) {
            return $tree;
        }

        foreach ($categoriesByParent[$parentId] as $category) {
            $categoryData = [
                'category' => $category,
                'children' => $this->buildCategoryTree($categoriesByParent, $category->getId())
            ];
            $tree[] = $categoryData;
        }

        return $tree;
    }
}