<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Model;

use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Order\Model\OrderStatus;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testCreateOrderWithoutUser(): void
    {
        $customerEmail = 'customer@example.com';
        $customerCompanyName = 'Test Company';
        $customerTaxId = '123456789';
        $shippingAddress = '123 Main St, City';
        $billingAddress = '456 Office St, City';
        $notes = 'Special delivery instructions';

        $order = new Order(
            $customerEmail,
            $customerCompanyName,
            $customerTaxId,
            $shippingAddress,
            $billingAddress,
            $notes
        );

        $this->assertEquals($customerEmail, $order->getCustomerEmail());
        $this->assertEquals($customerCompanyName, $order->getCustomerCompanyName());
        $this->assertEquals($customerTaxId, $order->getCustomerTaxId());
        $this->assertEquals($shippingAddress, $order->getShippingAddress());
        $this->assertEquals($billingAddress, $order->getBillingAddress());
        $this->assertEquals($notes, $order->getNotes());
        $this->assertNull($order->getUser());
        $this->assertEquals(OrderStatus::NEW, $order->getStatus());
        $this->assertEquals(0.0, $order->getTotalAmount());
        $this->assertInstanceOf(Collection::class, $order->getItems());
        $this->assertEmpty($order->getItems());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getCreatedAt());
        $this->assertNull($order->getUpdatedAt());
    }

    public function testCreateOrderWithUser(): void
    {
        $user = new User('user@example.com', 'password123', 'User Company', '987654321', UserRole::CLIENT);
        $customerEmail = 'customer@example.com';
        $customerCompanyName = 'Test Company';
        $shippingAddress = '123 Main St, City';
        $billingAddress = '456 Office St, City';

        $order = new Order(
            $customerEmail,
            $customerCompanyName,
            null,
            $shippingAddress,
            $billingAddress,
            null,
            $user
        );

        $this->assertSame($user, $order->getUser());
        $this->assertNull($order->getCustomerTaxId());
        $this->assertNull($order->getNotes());
    }

    public function testOrderNumberGeneration(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $orderNumber = $order->getOrderNumber();
        
        // Sprawdź format numeru zamówienia: YYYYMMDD-XXXXX
        $this->assertMatchesRegularExpression('/^\d{8}-\w{5}$/', $orderNumber);
        
        // Sprawdź czy data w numerze zamówienia to dzisiejsza data
        $today = date('Ymd');
        $this->assertStringStartsWith($today, $orderNumber);
    }

    public function testUniqueOrderNumbers(): void
    {
        // Test że każde zamówienie ma unikalny numer
        $order1 = new Order(
            'test1@example.com',
            'Test Company 1',
            null,
            'Address 1',
            'Address 1',
            null
        );
        
        $order2 = new Order(
            'test2@example.com',
            'Test Company 2',
            null,
            'Address 2',
            'Address 2',
            null
        );

        $this->assertNotEquals($order1->getOrderNumber(), $order2->getOrderNumber());
    }

    public function testSetters(): void
    {
        $order = new Order(
            'initial@example.com',
            'Initial Company',
            null,
            'Initial Shipping',
            'Initial Billing',
            null
        );

        $newUser = new User('new@example.com', 'password123', 'New Company');
        $order->setUser($newUser);
        $this->assertSame($newUser, $order->getUser());

        $newEmail = 'updated@example.com';
        $order->setCustomerEmail($newEmail);
        $this->assertEquals($newEmail, $order->getCustomerEmail());

        $newCompanyName = 'Updated Company';
        $order->setCustomerCompanyName($newCompanyName);
        $this->assertEquals($newCompanyName, $order->getCustomerCompanyName());

        $newTaxId = '999888777';
        $order->setCustomerTaxId($newTaxId);
        $this->assertEquals($newTaxId, $order->getCustomerTaxId());

        $newShippingAddress = 'New Shipping Address';
        $order->setShippingAddress($newShippingAddress);
        $this->assertEquals($newShippingAddress, $order->getShippingAddress());

        $newBillingAddress = 'New Billing Address';
        $order->setBillingAddress($newBillingAddress);
        $this->assertEquals($newBillingAddress, $order->getBillingAddress());

        $newNotes = 'Updated notes';
        $order->setNotes($newNotes);
        $this->assertEquals($newNotes, $order->getNotes());

        // Test unsetting optional fields
        $order->setUser(null);
        $this->assertNull($order->getUser());

        $order->setCustomerTaxId(null);
        $this->assertNull($order->getCustomerTaxId());

        $order->setNotes(null);
        $this->assertNull($order->getNotes());
    }

    public function testSetStatus(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $this->assertEquals(OrderStatus::NEW, $order->getStatus());
        $initialUpdatedAt = $order->getUpdatedAt();
        $this->assertNull($initialUpdatedAt);

        $order->setStatus(OrderStatus::PROCESSING);
        
        $this->assertEquals(OrderStatus::PROCESSING, $order->getStatus());
        $this->assertNotNull($order->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getUpdatedAt());
    }

    public function testAddItem(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $product = $this->createMock(Product::class);
        $orderItem = new OrderItem($product, 2, 15.99);

        $order->addItem($orderItem);

        $this->assertTrue($order->getItems()->contains($orderItem));
        $this->assertCount(1, $order->getItems());
        $this->assertSame($order, $orderItem->getOrder());
        $this->assertEquals(31.98, $order->getTotalAmount()); // 2 * 15.99
    }

    public function testAddDuplicateItem(): void
    {
        // Test że dodanie tego samego elementu nie powoduje duplikatów
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $product = $this->createMock(Product::class);
        $orderItem = new OrderItem($product, 1, 10.0);

        $order->addItem($orderItem);
        $order->addItem($orderItem); // Dodajemy ponownie ten sam element

        $this->assertCount(1, $order->getItems());
        $this->assertEquals(10.0, $order->getTotalAmount());
    }

    public function testRemoveItem(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $product = $this->createMock(Product::class);
        $orderItem = new OrderItem($product, 3, 12.50);

        $order->addItem($orderItem);
        $this->assertEquals(37.50, $order->getTotalAmount());

        $order->removeItem($orderItem);

        $this->assertFalse($order->getItems()->contains($orderItem));
        $this->assertCount(0, $order->getItems());
        $this->assertNull($orderItem->getOrder());
        $this->assertEquals(0.0, $order->getTotalAmount());
    }

    public function testRemoveNonExistentItem(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $product = $this->createMock(Product::class);
        $orderItem = new OrderItem($product, 1, 10.0);

        // Próba usunięcia elementu, który nie jest w zamówieniu
        $order->removeItem($orderItem);

        $this->assertCount(0, $order->getItems());
        $this->assertEquals(0.0, $order->getTotalAmount());
    }

    public function testRecalculateTotalAmount(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);

        $item1 = new OrderItem($product1, 2, 10.50); // 21.00
        $item2 = new OrderItem($product2, 1, 15.75); // 15.75

        $order->addItem($item1);
        $order->addItem($item2);

        $this->assertEquals(36.75, $order->getTotalAmount());

        // Zmień ilość w pierwszym elemencie
        $item1->setQuantity(3); // 3 * 10.50 = 31.50
        
        // Recalculate total
        $order->recalculateTotalAmount();

        $this->assertEquals(47.25, $order->getTotalAmount()); // 31.50 + 15.75
        $this->assertNotNull($order->getUpdatedAt());
    }

    public function testDecimalTotalAmountHandling(): void
    {
        // Test że total amount jest przechowywany jako string ale zwracany jako float
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        // Użyj reflection aby sprawdzić wewnętrzne przechowywanie
        $reflection = new \ReflectionProperty(Order::class, 'totalAmount');
        $reflection->setAccessible(true);

        $this->assertIsString($reflection->getValue($order));
        $this->assertEquals('0.00', $reflection->getValue($order));
        $this->assertIsFloat($order->getTotalAmount());
        $this->assertEquals(0.0, $order->getTotalAmount());

        // Dodaj element i sprawdź ponownie
        $product = $this->createMock(Product::class);
        $orderItem = new OrderItem($product, 1, 99.99);
        $order->addItem($orderItem);

        $this->assertIsString($reflection->getValue($order));
        $this->assertEquals('99.99', $reflection->getValue($order));
        $this->assertIsFloat($order->getTotalAmount());
        $this->assertEquals(99.99, $order->getTotalAmount());
    }

    public function testTimestampUpdates(): void
    {
        $order = new Order(
            'test@example.com',
            'Test Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $this->assertNull($order->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $order->getCreatedAt());

        // Zmiana statusu powinna zaktualizować timestamp
        $order->setStatus(OrderStatus::PROCESSING);
        $this->assertNotNull($order->getUpdatedAt());
        
        $firstUpdateTime = $order->getUpdatedAt();
        
        // Symulujemy upływ czasu
        sleep(1); // 1 second delay to ensure different timestamps
        
        // Recalculation powinna zaktualizować timestamp
        $order->recalculateTotalAmount();
        $this->assertGreaterThanOrEqual($firstUpdateTime->getTimestamp(), $order->getUpdatedAt()->getTimestamp());
    }

    public function testComplexOrderScenario(): void
    {
        // Test kompletnego scenariusza tworzenia i zarządzania zamówieniem
        $user = new User('customer@example.com', 'password123', 'Customer Company', '123456789', UserRole::CLIENT);
        
        $order = new Order(
            'customer@example.com',
            'Customer Company',
            '123456789',
            '123 Customer Street, Warsaw',
            '123 Customer Street, Warsaw',
            'Please ring the doorbell twice',
            $user
        );

        // Dodaj produkty
        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);
        $product3 = $this->createMock(Product::class);

        $item1 = new OrderItem($product1, 2, 25.99); // 51.98
        $item2 = new OrderItem($product2, 1, 99.50); // 99.50  
        $item3 = new OrderItem($product3, 3, 12.33); // 36.99

        $order->addItem($item1);
        $order->addItem($item2);
        $order->addItem($item3);

        // Sprawdź stan zamówienia
        $this->assertCount(3, $order->getItems());
        $this->assertEquals(188.47, $order->getTotalAmount());
        $this->assertEquals(OrderStatus::NEW, $order->getStatus());

        // Symuluj proces zamówienia
        $order->setStatus(OrderStatus::PROCESSING);
        $this->assertEquals(OrderStatus::PROCESSING, $order->getStatus());
        $this->assertNotNull($order->getUpdatedAt());

        // Usuń jeden element
        $order->removeItem($item2);
        $this->assertCount(2, $order->getItems());
        $this->assertEquals(88.97, $order->getTotalAmount()); // 51.98 + 36.99

        // Zakończ zamówienie
        $order->setStatus(OrderStatus::DELIVERED);
        $this->assertEquals(OrderStatus::DELIVERED, $order->getStatus());
    }
}