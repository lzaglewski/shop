<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Product;

use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductDeletionTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clear database
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE order_items');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE orders');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE products');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE users');
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testCanDeleteProductWithOrders(): void
    {
        // Create a product
        $product = new Product(
            name: 'Test Product',
            sku: 'TEST-001',
            description: 'Test description',
            basePrice: 100.00,
            stock: 10
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->getId();

        // Create a user
        $user = new User(
            email: 'test@example.com',
            password: 'hashed_password',
            companyName: 'Test Company',
            taxId: '1234567890',
            role: UserRole::CLIENT
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create an order with the product
        $order = new Order(
            customerEmail: 'test@example.com',
            customerCompanyName: 'Test Company',
            customerTaxId: '1234567890',
            shippingAddress: 'Test Street 1',
            billingAddress: 'Test Street 1',
            notes: null,
            user: $user
        );
        $orderItem = new OrderItem($product, 2, 100.00);
        $order->addItem($orderItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $orderItemId = $orderItem->getId();

        // Clear the entity manager to force fresh database queries
        $this->entityManager->clear();

        // Verify product and order item exist and are linked
        $product = $this->entityManager->find(Product::class, $productId);
        $orderItem = $this->entityManager->find(OrderItem::class, $orderItemId);

        $this->assertNotNull($product);
        $this->assertNotNull($orderItem);
        $this->assertNotNull($orderItem->getProduct());
        $this->assertSame($productId, $orderItem->getProduct()->getId());

        // Now delete the product
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Verify product is deleted
        $deletedProduct = $this->entityManager->find(Product::class, $productId);
        $this->assertNull($deletedProduct);

        // Verify order item still exists but product reference is null
        $orderItem = $this->entityManager->find(OrderItem::class, $orderItemId);
        $this->assertNotNull($orderItem);
        $this->assertNull($orderItem->getProduct());

        // Verify order item still has the captured product data
        $this->assertSame('Test Product', $orderItem->getProductName());
        $this->assertSame('TEST-001', $orderItem->getProductSku());
        $this->assertSame(100.00, $orderItem->getPrice());
        $this->assertSame(2, $orderItem->getQuantity());
    }

    public function testCanDeleteInactiveProductWithOrders(): void
    {
        // Create a product
        $product = new Product(
            name: 'Test Product 2',
            sku: 'TEST-002',
            description: 'Test description',
            basePrice: 200.00,
            stock: 5
        );

        // Mark as inactive (soft delete)
        $product->setIsActive(false);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->getId();

        // Create a user
        $user = new User(
            email: 'test2@example.com',
            password: 'hashed_password',
            companyName: 'Test Company 2',
            taxId: '0987654321',
            role: UserRole::CLIENT
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create an order with the inactive product
        $order = new Order(
            customerEmail: 'test2@example.com',
            customerCompanyName: 'Test Company 2',
            customerTaxId: '0987654321',
            shippingAddress: 'Test Street 2',
            billingAddress: 'Test Street 2',
            notes: null,
            user: $user
        );
        $orderItem = new OrderItem($product, 1, 200.00);
        $order->addItem($orderItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $orderItemId = $orderItem->getId();

        // Clear and reload
        $this->entityManager->clear();
        $product = $this->entityManager->find(Product::class, $productId);

        // Delete the inactive product (hard delete)
        $this->entityManager->remove($product);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Verify the order item preserved product data
        $orderItem = $this->entityManager->find(OrderItem::class, $orderItemId);
        $this->assertNotNull($orderItem);
        $this->assertNull($orderItem->getProduct());
        $this->assertSame('Test Product 2', $orderItem->getProductName());
        $this->assertSame('TEST-002', $orderItem->getProductSku());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
