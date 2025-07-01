<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product\Model;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\User\Model\User;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\Collection;

class ProductTest extends TestCase
{
    public function testCreateProduct(): void
    {
        $name = 'Test Product';
        $sku = 'TP001';
        $description = 'Test product description';
        $basePrice = 99.99;
        $stock = 10;
        $category = $this->createMock(ProductCategory::class);
        $imageFilename = 'test-image.jpg';

        $product = new Product($name, $sku, $description, $basePrice, $stock, $category, $imageFilename);

        $this->assertEquals($name, $product->getName());
        $this->assertEquals($sku, $product->getSku());
        $this->assertEquals($description, $product->getDescription());
        $this->assertEquals($basePrice, $product->getBasePrice());
        $this->assertEquals($stock, $product->getStock());
        $this->assertSame($category, $product->getCategory());
        $this->assertEquals($imageFilename, $product->getImageFilename());
        $this->assertTrue($product->isActive());
        $this->assertInstanceOf(Collection::class, $product->getClientPrices());
        $this->assertEmpty($product->getClientPrices());
    }

    public function testSetters(): void
    {
        $product = new Product('Initial', 'INIT001', 'Initial description', 10.0, 5);
        
        $newName = 'Updated Product';
        $product->setName($newName);
        $this->assertEquals($newName, $product->getName());
        
        $newSku = 'UPD001';
        $product->setSku($newSku);
        $this->assertEquals($newSku, $product->getSku());
        
        $newDescription = 'Updated description';
        $product->setDescription($newDescription);
        $this->assertEquals($newDescription, $product->getDescription());
        
        $newBasePrice = 19.99;
        $product->setBasePrice($newBasePrice);
        $this->assertEquals($newBasePrice, $product->getBasePrice());
        
        $newStock = 20;
        $product->setStock($newStock);
        $this->assertEquals($newStock, $product->getStock());
        
        $product->setIsActive(false);
        $this->assertFalse($product->isActive());
        
        $newImageFilename = 'new-image.jpg';
        $product->setImageFilename($newImageFilename);
        $this->assertEquals($newImageFilename, $product->getImageFilename());
        
        $newCategory = $this->createMock(ProductCategory::class);
        $product->setCategory($newCategory);
        $this->assertSame($newCategory, $product->getCategory());
    }

    public function testClientPriceManagement(): void
    {
        // Tworzymy rzeczywiste obiekty
        $product = new Product('Test Product', 'TST-001', 'Test description', 10.0, 5);
        $client = new User('client@example.com', 'password123', 'Test Company');
        
        // Tworzymy obiekt ClientPrice, ale nie dodajemy go automatycznie do produktu
        // (konstruktor ClientPrice ustawia produkt, ale nie dodaje ClientPrice do kolekcji produktu)
        $clientPrice = new ClientPrice($client, $product, 15.0);
        
        // Ręcznie dodajemy ClientPrice do produktu
        $product->addClientPrice($clientPrice);
        
        // Sprawdzamy, czy produkt zawiera cenę klienta w kolekcji
        $this->assertTrue($product->getClientPrices()->contains($clientPrice));
        $this->assertCount(1, $product->getClientPrices());
    }
}
