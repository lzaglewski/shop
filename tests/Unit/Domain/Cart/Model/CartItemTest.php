<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Cart\Model;

use App\Domain\Cart\Model\Cart;
use App\Domain\Cart\Model\CartItem;
use App\Domain\Product\Model\Product;
use PHPUnit\Framework\TestCase;

class CartItemTest extends TestCase
{
    public function testCreateCartItem(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(19.99);
        
        $quantity = 3;
        $cartItem = new CartItem($product, $quantity);

        $this->assertSame($product, $cartItem->getProduct());
        $this->assertEquals($quantity, $cartItem->getQuantity());
        $this->assertEquals(19.99, $cartItem->getPrice());
        $this->assertNull($cartItem->getCart());
    }

    public function testCreateCartItemWithDefaultQuantity(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(25.50);
        
        $cartItem = new CartItem($product);

        $this->assertSame($product, $cartItem->getProduct());
        $this->assertEquals(1, $cartItem->getQuantity());
        $this->assertEquals(25.50, $cartItem->getPrice());
    }

    public function testSetCart(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $cartItem = new CartItem($product, 2);
        $cart = $this->createMock(Cart::class);
        
        $cartItem->setCart($cart);
        $this->assertSame($cart, $cartItem->getCart());
        
        // Test unsetting cart
        $cartItem->setCart(null);
        $this->assertNull($cartItem->getCart());
    }

    public function testSetQuantity(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $cartItem = new CartItem($product, 1);
        
        $newQuantity = 5;
        $cartItem->setQuantity($newQuantity);
        $this->assertEquals($newQuantity, $cartItem->getQuantity());
        
        // Test setting quantity to zero
        $cartItem->setQuantity(0);
        $this->assertEquals(0, $cartItem->getQuantity());
    }

    public function testSetPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $cartItem = new CartItem($product, 1);
        
        $newPrice = 15.75;
        $cartItem->setPrice($newPrice);
        $this->assertEquals($newPrice, $cartItem->getPrice());
    }

    public function testDecimalPriceHandling(): void
    {
        // Test że cena jest przechowywana jako string ale zwracana jako float
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(99.99);
        
        $cartItem = new CartItem($product, 1);
        
        // Użyj reflection aby sprawdzić wewnętrzne przechowywanie
        $reflection = new \ReflectionProperty(CartItem::class, 'price');
        $reflection->setAccessible(true);
        
        $this->assertIsString($reflection->getValue($cartItem));
        $this->assertEquals('99.99', $reflection->getValue($cartItem));
        $this->assertIsFloat($cartItem->getPrice());
        $this->assertEquals(99.99, $cartItem->getPrice());
    }

    public function testSetPriceDecimalConversion(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $cartItem = new CartItem($product, 1);
        
        $newPrice = 45.67;
        $cartItem->setPrice($newPrice);
        
        // Sprawdź wewnętrzne przechowywanie
        $reflection = new \ReflectionProperty(CartItem::class, 'price');
        $reflection->setAccessible(true);
        
        $this->assertIsString($reflection->getValue($cartItem));
        $this->assertEquals((string)$newPrice, $reflection->getValue($cartItem));
        $this->assertIsFloat($cartItem->getPrice());
        $this->assertEquals($newPrice, $cartItem->getPrice());
    }

    public function testGetSubtotal(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(12.50);
        
        $quantity = 4;
        $cartItem = new CartItem($product, $quantity);
        
        $expectedSubtotal = 12.50 * 4; // 50.0
        $this->assertEquals($expectedSubtotal, $cartItem->getSubtotal());
    }

    public function testGetSubtotalWithCustomPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $cartItem = new CartItem($product, 3);
        
        // Ustaw indywidualną cenę (np. cenę klienta)
        $cartItem->setPrice(8.50);
        
        $expectedSubtotal = 8.50 * 3; // 25.5
        $this->assertEquals($expectedSubtotal, $cartItem->getSubtotal());
    }

    public function testGetSubtotalWithZeroQuantity(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(15.0);
        
        $cartItem = new CartItem($product, 2);
        $cartItem->setQuantity(0);
        
        $this->assertEquals(0.0, $cartItem->getSubtotal());
    }

    public function testPricePrecision(): void
    {
        // Test obsługi precyzyjnych cen
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(9.999);
        
        $cartItem = new CartItem($product, 1);
        
        $this->assertEquals(9.999, $cartItem->getPrice());
        $this->assertEquals(9.999, $cartItem->getSubtotal());
        
        // Test z większą precyzją
        $cartItem->setPrice(12.3456789);
        $this->assertEquals(12.3456789, $cartItem->getPrice());
        
        $cartItem->setQuantity(2);
        $this->assertEquals(24.6913578, $cartItem->getSubtotal());
    }

    public function testBidirectionalRelationshipWithCart(): void
    {
        // Test dwukierunkowej relacji z Cart
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        $product->method('getId')->willReturn(1);
        
        $cartItem = new CartItem($product, 2);
        $cart = new Cart();
        
        // Dodaj element do koszyka - powinno ustawić cart w CartItem
        $cart->addItem($cartItem);
        
        $this->assertSame($cart, $cartItem->getCart());
        $this->assertTrue($cart->getItems()->contains($cartItem));
        
        // Usuń element z koszyka
        $cart->removeItem($cartItem);
        
        $this->assertNull($cartItem->getCart());
        $this->assertFalse($cart->getItems()->contains($cartItem));
    }
}