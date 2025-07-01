<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product\Model;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ProductCategoryTest extends TestCase
{
    public function testCreateProductCategory(): void
    {
        $name = 'Test Category';
        $description = 'Test category description';
        $parent = $this->createMock(ProductCategory::class);

        $category = new ProductCategory($name, $description, $parent);

        $this->assertEquals($name, $category->getName());
        $this->assertEquals($description, $category->getDescription());
        $this->assertSame($parent, $category->getParent());
        $this->assertInstanceOf(Collection::class, $category->getProducts());
        $this->assertEmpty($category->getProducts());
        $this->assertInstanceOf(Collection::class, $category->getChildren());
        $this->assertEmpty($category->getChildren());
    }

    public function testSetters(): void
    {
        $category = new ProductCategory('Initial Category');
        
        $newName = 'Updated Category';
        $category->setName($newName);
        $this->assertEquals($newName, $category->getName());
        
        $newDescription = 'Updated description';
        $category->setDescription($newDescription);
        $this->assertEquals($newDescription, $category->getDescription());
        
        $newParent = $this->createMock(ProductCategory::class);
        $category->setParent($newParent);
        $this->assertSame($newParent, $category->getParent());
    }

    public function testProductManagement(): void
    {
        $category = new ProductCategory('Test Category');
        
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('setCategory')
            ->with($this->identicalTo($category));
            
        $category->addProduct($product);
        $this->assertCount(1, $category->getProducts());
        $this->assertTrue($category->getProducts()->contains($product));
        
        // Konfigurujemy mock dla getCategory i setCategory
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getCategory')
            ->willReturn($category);
            
        $product->expects($this->once())
            ->method('setCategory')
            ->with(null);
        
        // Dodajemy produkt do kolekcji ręcznie, ponieważ używamy nowego mocka
        $reflection = new \ReflectionObject($category);
        $productsProperty = $reflection->getProperty('products');
        $productsProperty->setAccessible(true);
        $products = $productsProperty->getValue($category);
        $products->clear(); // Czyścimy kolekcję przed dodaniem
        $products->add($product);
            
        $category->removeProduct($product);
        $this->assertCount(0, $category->getProducts());
    }

    public function testChildCategoryManagement(): void
    {
        $parentCategory = new ProductCategory('Parent Category');
        $childCategory = new ProductCategory('Child Category');
        
        $parentCategory->addChild($childCategory);
        $this->assertCount(1, $parentCategory->getChildren());
        $this->assertTrue($parentCategory->getChildren()->contains($childCategory));
        $this->assertSame($parentCategory, $childCategory->getParent());
        
        $parentCategory->removeChild($childCategory);
        $this->assertCount(0, $parentCategory->getChildren());
        $this->assertNull($childCategory->getParent());
    }
}
