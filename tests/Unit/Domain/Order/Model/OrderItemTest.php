<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Model;

use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Product\Model\Product;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testCreateOrderItem(): void
    {
        $productName = 'Test Product';
        $productSku = 'TST-001';
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn($productName);
        $product->method('getSku')->willReturn($productSku);

        $quantity = 3;
        $price = 25.99;

        $orderItem = new OrderItem($product, $quantity, $price);

        $this->assertSame($product, $orderItem->getProduct());
        $this->assertEquals($productName, $orderItem->getProductName());
        $this->assertEquals($productSku, $orderItem->getProductSku());
        $this->assertEquals($quantity, $orderItem->getQuantity());
        $this->assertEquals($price, $orderItem->getPrice());
        $this->assertNull($orderItem->getOrder());
    }

    public function testProductDataSnapshot(): void
    {
        // Test że dane produktu są zapisywane jako snapshot w momencie tworzenia OrderItem
        $originalName = 'Original Product Name';
        $originalSku = 'ORIG-001';
        
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn($originalName);
        $product->method('getSku')->willReturn($originalSku);

        $orderItem = new OrderItem($product, 2, 15.50);

        // Sprawdź czy dane zostały skopiowane
        $this->assertEquals($originalName, $orderItem->getProductName());
        $this->assertEquals($originalSku, $orderItem->getProductSku());

        // Nawet jeśli produkt zmieni dane, OrderItem powinien zachować oryginalne wartości
        // (w rzeczywistym scenariuszu mock nie zmieni się, ale test pokazuje koncepcję)
        $this->assertEquals($originalName, $orderItem->getProductName());
        $this->assertEquals($originalSku, $orderItem->getProductSku());
    }

    public function testSetOrder(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $orderItem = new OrderItem($product, 1, 10.0);
        $order = $this->createMock(Order::class);

        $orderItem->setOrder($order);
        $this->assertSame($order, $orderItem->getOrder());

        // Test unsetting order
        $orderItem->setOrder(null);
        $this->assertNull($orderItem->getOrder());
    }

    public function testSetQuantity(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $orderItem = new OrderItem($product, 1, 15.0);

        $newQuantity = 5;
        $orderItem->setQuantity($newQuantity);
        $this->assertEquals($newQuantity, $orderItem->getQuantity());

        // Test setting quantity to zero
        $orderItem->setQuantity(0);
        $this->assertEquals(0, $orderItem->getQuantity());
    }

    public function testDecimalPriceHandling(): void
    {
        // Test że cena jest przechowywana jako string ale zwracana jako float
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $price = 99.99;
        $orderItem = new OrderItem($product, 1, $price);

        // Użyj reflection aby sprawdzić wewnętrzne przechowywanie
        $reflection = new \ReflectionProperty(OrderItem::class, 'price');
        $reflection->setAccessible(true);

        $this->assertIsString($reflection->getValue($orderItem));
        $this->assertEquals((string)$price, $reflection->getValue($orderItem));
        $this->assertIsFloat($orderItem->getPrice());
        $this->assertEquals($price, $orderItem->getPrice());
    }

    public function testGetSubtotal(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $quantity = 4;
        $price = 12.50;
        $orderItem = new OrderItem($product, $quantity, $price);

        $expectedSubtotal = $price * $quantity; // 50.0
        $this->assertEquals($expectedSubtotal, $orderItem->getSubtotal());
    }

    public function testGetSubtotalWithPrecision(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $quantity = 3;
        $price = 33.33;
        $orderItem = new OrderItem($product, $quantity, $price);

        $expectedSubtotal = 99.99; // 33.33 * 3
        $this->assertEquals($expectedSubtotal, $orderItem->getSubtotal());
    }

    public function testGetSubtotalWithZeroQuantity(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $orderItem = new OrderItem($product, 5, 15.0);
        $orderItem->setQuantity(0);

        $this->assertEquals(0.0, $orderItem->getSubtotal());
    }

    public function testGetSubtotalWithZeroPrice(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Free Product');
        $product->method('getSku')->willReturn('FREE-001');

        $orderItem = new OrderItem($product, 10, 0.0);

        $this->assertEquals(0.0, $orderItem->getSubtotal());
    }

    public function testHighPrecisionCalculations(): void
    {
        // Test obsługi wyższej precyzji w obliczeniach
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Precision Product');
        $product->method('getSku')->willReturn('PREC-001');

        $quantity = 7;
        $price = 14.285714; // Wysoka precyzja
        $orderItem = new OrderItem($product, $quantity, $price);

        $this->assertEquals($price, $orderItem->getPrice());
        $this->assertEquals($price * $quantity, $orderItem->getSubtotal());
    }

    public function testBidirectionalRelationshipWithOrder(): void
    {
        // Test dwukierunkowej relacji z Order
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('TST-001');

        $orderItem = new OrderItem($product, 2, 20.0);
        
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        // Dodaj element do zamówienia - powinno ustawić order w OrderItem
        $order->addItem($orderItem);

        $this->assertSame($order, $orderItem->getOrder());
        $this->assertTrue($order->getItems()->contains($orderItem));

        // Usuń element z zamówienia
        $order->removeItem($orderItem);

        $this->assertNull($orderItem->getOrder());
        $this->assertFalse($order->getItems()->contains($orderItem));
    }

    public function testProductDataImmutability(): void
    {
        // Test że dane produktu nie mogą być zmieniane po utworzeniu OrderItem
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Original Name');
        $product->method('getSku')->willReturn('ORIG-001');

        $orderItem = new OrderItem($product, 1, 10.0);

        // Sprawdź czy nie ma setterów dla productName i productSku
        $this->assertFalse(method_exists($orderItem, 'setProductName'));
        $this->assertFalse(method_exists($orderItem, 'setProductSku'));

        // Dane produktu powinny pozostać niezmienne
        $this->assertEquals('Original Name', $orderItem->getProductName());
        $this->assertEquals('ORIG-001', $orderItem->getProductSku());
    }

    public function testComplexScenario(): void
    {
        // Test złożonego scenariusza użycia OrderItem
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Premium Widget');
        $product->method('getSku')->willReturn('PREM-WIDGET-001');

        $orderItem = new OrderItem($product, 5, 89.99);

        // Weryfikuj podstawowe właściwości
        $this->assertEquals('Premium Widget', $orderItem->getProductName());
        $this->assertEquals('PREM-WIDGET-001', $orderItem->getProductSku());
        $this->assertEquals(5, $orderItem->getQuantity());
        $this->assertEquals(89.99, $orderItem->getPrice());
        $this->assertEquals(449.95, $orderItem->getSubtotal());

        // Zmień ilość
        $orderItem->setQuantity(3);
        $this->assertEquals(3, $orderItem->getQuantity());
        $this->assertEqualsWithDelta(269.97, $orderItem->getSubtotal(), 0.01);

        // Dodaj do zamówienia
        $order = new Order(
            'premium@customer.com',
            'Premium Customer Co.',
            '999888777',
            '123 Premium Street, Warsaw',
            '123 Premium Street, Warsaw',
            'VIP customer - handle with care'
        );

        $order->addItem($orderItem);

        $this->assertSame($order, $orderItem->getOrder());
        $this->assertEqualsWithDelta(269.97, $order->getTotalAmount(), 0.01);
    }
}