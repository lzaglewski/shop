<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Application\Email\EmailService;
use App\Application\Email\OrderEmailDataResolver;
use App\Domain\Event\OrderCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OrderCreatedEvent::NAME)]
class OrderCreatedEmailListener
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly OrderEmailDataResolver $dataResolver,
        private readonly bool $emailNotificationsEnabled = true
    ) {
    }

    public function __invoke(OrderCreatedEvent $event): void
    {
        if (!$this->emailNotificationsEnabled) {
            return;
        }

        $order = $event->getOrder();
        $orderData = $this->dataResolver->resolveOrderData($order);
        $customerName = $this->dataResolver->getCustomerName($order);

        try {
            // Send email to customer
            $this->emailService->sendOrderCreatedCustomerEmail(
                $order->getCustomerEmail(),
                $customerName,
                $order->getOrderNumber(),
                $orderData
            );

            // Send email to admin
            $this->emailService->sendOrderCreatedAdminEmail(
                $order->getOrderNumber(),
                $orderData
            );
        } catch (\Exception $e) {
            // Log error but don't interrupt the order creation process
            error_log('Failed to send order created email: ' . $e->getMessage());
        }
    }
}