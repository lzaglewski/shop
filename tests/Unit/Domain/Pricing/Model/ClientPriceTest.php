<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Pricing\Model;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use PHPUnit\Framework\TestCase;

class ClientPriceTest extends TestCase
{
    public function testCreateClientPrice(): void
    {
        $client = $this->createMock(User::class);
        $product = $this->createMock(Product::class);
        $price = 89.99;

        $clientPrice = new ClientPrice($client, $product, $price);

        $this->assertSame($client, $clientPrice->getClient());
        $this->assertSame($product, $clientPrice->getProduct());
        $this->assertEquals($price, $clientPrice->getPrice());
        $this->assertTrue($clientPrice->isActive());
    }

    public function testSetters(): void
    {
        $initialClient = $this->createMock(User::class);
        $initialProduct = $this->createMock(Product::class);
        $initialPrice = 10.0;

        $clientPrice = new ClientPrice($initialClient, $initialProduct, $initialPrice);
        
        $newClient = $this->createMock(User::class);
        $clientPrice->setClient($newClient);
        $this->assertSame($newClient, $clientPrice->getClient());
        
        $newProduct = $this->createMock(Product::class);
        $clientPrice->setProduct($newProduct);
        $this->assertSame($newProduct, $clientPrice->getProduct());
        
        $newPrice = 19.99;
        $clientPrice->setPrice($newPrice);
        $this->assertEquals($newPrice, $clientPrice->getPrice());
        
        $clientPrice->setIsActive(false);
        $this->assertFalse($clientPrice->isActive());
    }

    public function testDecimalConversion(): void
    {
        $client = $this->createMock(User::class);
        $product = $this->createMock(Product::class);
        $price = 99.99;

        $clientPrice = new ClientPrice($client, $product, $price);
        
        // Test that the price is stored as string internally but returned as float
        $reflection = new \ReflectionProperty(ClientPrice::class, 'price');
        $reflection->setAccessible(true);
        
        $this->assertIsString($reflection->getValue($clientPrice));
        $this->assertEquals((string)$price, $reflection->getValue($clientPrice));
        $this->assertIsFloat($clientPrice->getPrice());
        $this->assertEquals($price, $clientPrice->getPrice());
    }
}
