<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\EventListener;

use App\Application\Email\EmailService;
use App\Application\Email\OrderEmailDataResolver;
use App\Application\EventListener\OrderCreatedEmailListener;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Order\Model\Order;
use App\Domain\User\Model\User;
use PHPUnit\Framework\TestCase;

class OrderCreatedEmailListenerTest extends TestCase
{
    private EmailService $emailService;
    private OrderEmailDataResolver $dataResolver;
    private OrderCreatedEmailListener $listener;

    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
        $this->dataResolver = $this->createMock(OrderEmailDataResolver::class);
        
        $this->listener = new OrderCreatedEmailListener(
            $this->emailService,
            $this->dataResolver,
            true // notifications enabled
        );
    }

    public function testInvokeWithNotificationsEnabled(): void
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
            '1234567890',
            'Shipping Address',
            'Billing Address',
            'Notes',
            $user
        );

        $orderData = [
            'orderNumber' => $order->getOrderNumber(),
            'customerEmail' => $order->getCustomerEmail()
        ];

        $customerName = 'Test Company';

        $this->dataResolver
            ->expects($this->once())
            ->method('resolveOrderData')
            ->with($order)
            ->willReturn($orderData);

        $this->dataResolver
            ->expects($this->once())
            ->method('getCustomerName')
            ->with($order)
            ->willReturn($customerName);

        $this->emailService
            ->expects($this->once())
            ->method('sendOrderCreatedCustomerEmail')
            ->with(
                $order->getCustomerEmail(),
                $customerName,
                $order->getOrderNumber(),
                $orderData
            );

        $this->emailService
            ->expects($this->once())
            ->method('sendOrderCreatedAdminEmail')
            ->with(
                $order->getOrderNumber(),
                $orderData
            );

        $event = new OrderCreatedEvent($order);
        $this->listener->__invoke($event);
    }

    public function testInvokeWithNotificationsDisabled(): void
    {
        $listener = new OrderCreatedEmailListener(
            $this->emailService,
            $this->dataResolver,
            false // notifications disabled
        );

        $order = new Order(
            'customer@example.com',
            'Customer Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $this->emailService
            ->expects($this->never())
            ->method('sendOrderCreatedCustomerEmail');

        $this->emailService
            ->expects($this->never())
            ->method('sendOrderCreatedAdminEmail');

        $event = new OrderCreatedEvent($order);
        $listener->__invoke($event);
    }

    public function testInvokeHandlesEmailException(): void
    {
        $order = new Order(
            'customer@example.com',
            'Customer Company',
            null,
            'Shipping Address',
            'Billing Address',
            null
        );

        $this->dataResolver
            ->expects($this->once())
            ->method('resolveOrderData')
            ->with($order)
            ->willReturn([]);

        $this->dataResolver
            ->expects($this->once())
            ->method('getCustomerName')
            ->with($order)
            ->willReturn('Customer Company');

        $this->emailService
            ->expects($this->once())
            ->method('sendOrderCreatedCustomerEmail')
            ->willThrowException(new \Exception('Email sending failed'));

        // Should not throw exception and continue execution
        $event = new OrderCreatedEvent($order);
        $this->listener->__invoke($event);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }
}