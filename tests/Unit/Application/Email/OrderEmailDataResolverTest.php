<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Email;

use App\Application\Email\OrderEmailDataResolver;
use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Order\Model\OrderStatus;
use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\User\Model\User;
use PHPUnit\Framework\TestCase;

class OrderEmailDataResolverTest extends TestCase
{
    private OrderEmailDataResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderEmailDataResolver();
    }

    public function testResolveOrderDataWithUser(): void
    {
        $user = new User(
            'user@example.com',
            'password123',
            'Test Company',
            '1234567890'
        );

        $order = new Order(
            'customer@example.com',
            'Customer Company',
            '9876543210',
            'Shipping Address',
            'Billing Address',
            'Test notes',
            $user
        );

        $category = new ProductCategory('Test Category');
        $product = new Product(
            'Test Product',
            'PROD123',
            'Product description',
            99.99,
            10,
            $category
        );

        $orderItem = new OrderItem($product, 2, 89.99);
        $order->addItem($orderItem);

        $result = $this->resolver->resolveOrderData($order);

        $this->assertEquals($order->getOrderNumber(), $result['orderNumber']);
        $this->assertEquals('customer@example.com', $result['customerEmail']);
        $this->assertEquals('Customer Company', $result['customerCompanyName']);
        $this->assertEquals('9876543210', $result['customerTaxId']);
        $this->assertEquals('Shipping Address', $result['shippingAddress']);
        $this->assertEquals('Billing Address', $result['billingAddress']);
        $this->assertEquals('Test notes', $result['notes']);
        $this->assertEquals('Nowe', $result['status']);
        $this->assertEquals('new', $result['statusKey']);
        $this->assertNotNull($result['user']);
        $this->assertEquals('user@example.com', $result['user']['email']);
        $this->assertEquals('Test Company', $result['user']['companyName']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals('Test Product', $result['items'][0]['productName']);
        $this->assertEquals('PROD123', $result['items'][0]['productSku']);
        $this->assertEquals(2, $result['items'][0]['quantity']);
        $this->assertEquals(89.99, $result['items'][0]['price']);
    }

    public function testResolveOrderDataWithoutUser(): void
    {
        $order = new Order(
            'guest@example.com',
            'Guest Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $result = $this->resolver->resolveOrderData($order);

        $this->assertEquals('guest@example.com', $result['customerEmail']);
        $this->assertEquals('Guest Company', $result['customerCompanyName']);
        $this->assertNull($result['customerTaxId']);
        $this->assertNull($result['notes']);
        $this->assertNull($result['user']);
        $this->assertEmpty($result['items']);
    }

    public function testGetCustomerNameWithUser(): void
    {
        $user = new User(
            'user@example.com',
            'password123',
            'User Company',
            '1234567890'
        );

        $order = new Order(
            'customer@example.com',
            'Customer Company',
            null,
            'Shipping Address',
            'Billing Address',
            null,
            $user
        );

        $result = $this->resolver->getCustomerName($order);

        $this->assertEquals('User Company', $result);
    }

    public function testGetCustomerNameWithoutUser(): void
    {
        $order = new Order(
            'guest@example.com',
            'Guest Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $result = $this->resolver->getCustomerName($order);

        $this->assertEquals('Guest Company', $result);
    }

    public function testGetStatusDisplayName(): void
    {
        $this->assertEquals('Nowe', $this->resolver->getStatusDisplayName(OrderStatus::NEW));
        $this->assertEquals('W trakcie realizacji', $this->resolver->getStatusDisplayName(OrderStatus::PROCESSING));
        $this->assertEquals('Wysłane', $this->resolver->getStatusDisplayName(OrderStatus::SHIPPED));
        $this->assertEquals('Dostarczone', $this->resolver->getStatusDisplayName(OrderStatus::DELIVERED));
        $this->assertEquals('Anulowane', $this->resolver->getStatusDisplayName(OrderStatus::CANCELLED));
    }
}