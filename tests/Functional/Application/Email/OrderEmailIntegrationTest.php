<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Email;

use App\Application\Email\EmailService;
use App\Application\Email\OrderEmailDataResolver;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Event\OrderStatusChangedEvent;
use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Order\Model\OrderStatus;
use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use App\Domain\User\Model\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderEmailIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EmailService $emailService;
    private OrderEmailDataResolver $dataResolver;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->emailService = $container->get(EmailService::class);
        $this->dataResolver = $container->get(OrderEmailDataResolver::class);
        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
        
        // Clear database
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE order_items');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE orders');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE products');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE product_categories');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE users');
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        
        $this->entityManager->clear();
    }

    public function testEmailServiceIntegration(): void
    {
        // Create test data
        $category = new ProductCategory('Test Category');
        $this->entityManager->persist($category);

        $product = new Product(
            'Test Product',
            'PROD123',
            'Test description',
            99.99,
            10,
            $category
        );
        $this->entityManager->persist($product);

        $user = new User(
            'test@example.com',
            'password123',
            'Test Company',
            '1234567890'
        );
        $this->entityManager->persist($user);

        $this->entityManager->flush();

        // Create order manually
        $order = new Order(
            'customer@example.com',
            'Customer Company',
            '9876543210',
            'Test shipping address',
            'Test billing address',
            'Test notes',
            $user
        );

        $orderItem = new OrderItem($product, 2, 89.99);
        $order->addItem($orderItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Test email data resolver
        $orderData = $this->dataResolver->resolveOrderData($order);
        $this->assertEquals($order->getOrderNumber(), $orderData['orderNumber']);
        $this->assertEquals('customer@example.com', $orderData['customerEmail']);
        $this->assertEquals('Customer Company', $orderData['customerCompanyName']);
        $this->assertNotNull($orderData['user']);
        $this->assertEquals('test@example.com', $orderData['user']['email']);

        // Test customer name resolution
        $customerName = $this->dataResolver->getCustomerName($order);
        $this->assertEquals('Test Company', $customerName);

        // Test that emails can be sent without throwing exceptions
        try {
            $this->emailService->sendOrderCreatedCustomerEmail(
                $order->getCustomerEmail(),
                $customerName,
                $order->getOrderNumber(),
                $orderData
            );
            $this->assertTrue(true, 'Customer email sent successfully');
        } catch (\Exception $e) {
            $this->fail('Customer email failed: ' . $e->getMessage());
        }

        try {
            $this->emailService->sendOrderCreatedAdminEmail(
                $order->getOrderNumber(),
                $orderData
            );
            $this->assertTrue(true, 'Admin email sent successfully');
        } catch (\Exception $e) {
            $this->fail('Admin email failed: ' . $e->getMessage());
        }
    }

    public function testEventDispatcherIntegration(): void
    {
        // Create test data
        $category = new ProductCategory('Test Category');
        $this->entityManager->persist($category);

        $product = new Product(
            'Test Product',
            'PROD123',
            'Test description',
            99.99,
            10,
            $category
        );
        $this->entityManager->persist($product);

        $this->entityManager->flush();

        // Create order manually
        $order = new Order(
            'customer@example.com',
            'Customer Company',
            null,
            'Test shipping address',
            'Test billing address',
            null
        );

        $orderItem = new OrderItem($product, 1, 99.99);
        $order->addItem($orderItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        // Test event dispatching
        $orderCreatedEvent = new OrderCreatedEvent($order);
        
        try {
            $this->eventDispatcher->dispatch($orderCreatedEvent, OrderCreatedEvent::NAME);
            $this->assertTrue(true, 'OrderCreatedEvent dispatched successfully');
        } catch (\Exception $e) {
            $this->fail('Event dispatch failed: ' . $e->getMessage());
        }

        // Test status change event
        $previousStatus = $order->getStatus();
        $order->setStatus(OrderStatus::SHIPPED);
        
        $statusChangedEvent = new OrderStatusChangedEvent($order, $previousStatus, OrderStatus::SHIPPED);
        
        try {
            $this->eventDispatcher->dispatch($statusChangedEvent, OrderStatusChangedEvent::NAME);
            $this->assertTrue(true, 'OrderStatusChangedEvent dispatched successfully');
        } catch (\Exception $e) {
            $this->fail('Status change event dispatch failed: ' . $e->getMessage());
        }
    }

    public function testEmailConfigurationAccess(): void
    {
        // Test that email configuration is accessible
        $container = static::getContainer();
        
        $this->assertTrue($container->hasParameter('email.from_email'));
        $this->assertTrue($container->hasParameter('email.from_name'));
        $this->assertTrue($container->hasParameter('email.admin_emails'));
        $this->assertTrue($container->hasParameter('email.notifications_enabled'));
        
        $fromEmail = $container->getParameter('email.from_email');
        $fromName = $container->getParameter('email.from_name');
        $adminEmails = $container->getParameter('email.admin_emails');
        $notificationsEnabled = $container->getParameter('email.notifications_enabled');
        
        $this->assertIsString($fromEmail);
        $this->assertIsString($fromName);
        $this->assertIsArray($adminEmails);
        $this->assertIsBool($notificationsEnabled);
        
        // Test that services are properly configured
        $this->assertInstanceOf(EmailService::class, $this->emailService);
        $this->assertInstanceOf(OrderEmailDataResolver::class, $this->dataResolver);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}