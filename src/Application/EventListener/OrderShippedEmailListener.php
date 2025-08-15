<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Application\Email\EmailService;
use App\Application\Email\OrderEmailDataResolver;
use App\Domain\Event\OrderShippedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OrderShippedEvent::NAME)]
class OrderShippedEmailListener
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly OrderEmailDataResolver $dataResolver,
        private readonly bool $emailNotificationsEnabled = true
    ) {
    }

    public function __invoke(OrderShippedEvent $event): void
    {
        if (!$this->emailNotificationsEnabled) {
            return;
        }

        $order = $event->getOrder();
        $orderData = $this->dataResolver->resolveOrderData($order);
        $customerName = $this->dataResolver->getCustomerName($order);

        try {
            $this->emailService->sendOrderShippedEmail(
                $order->getCustomerEmail(),
                $customerName,
                $order->getOrderNumber(),
                $event->getTrackingNumber(),
                $orderData
            );
        } catch (\Exception $e) {
            // Log error but don't interrupt the shipping process
            error_log('Failed to send order shipped email: ' . $e->getMessage());
        }
    }
}