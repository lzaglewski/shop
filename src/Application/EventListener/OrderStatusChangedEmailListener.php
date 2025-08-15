<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Application\Email\EmailService;
use App\Application\Email\OrderEmailDataResolver;
use App\Domain\Event\OrderStatusChangedEvent;
use App\Domain\Order\Model\OrderStatus;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: OrderStatusChangedEvent::NAME)]
class OrderStatusChangedEmailListener
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly OrderEmailDataResolver $dataResolver,
        private readonly bool $emailNotificationsEnabled = true
    ) {
    }

    public function __invoke(OrderStatusChangedEvent $event): void
    {
        if (!$this->emailNotificationsEnabled) {
            return;
        }

        $order = $event->getOrder();
        $orderData = $this->dataResolver->resolveOrderData($order);
        $customerName = $this->dataResolver->getCustomerName($order);

        try {
            $this->emailService->sendOrderStatusChangedEmail(
                $order->getCustomerEmail(),
                $customerName,
                $order->getOrderNumber(),
                $this->dataResolver->getStatusDisplayName($event->getPreviousStatus()),
                $this->dataResolver->getStatusDisplayName($event->getNewStatus()),
                $orderData
            );

            // If order was shipped, send special shipping notification
            if ($event->getNewStatus() === OrderStatus::SHIPPED) {
                $this->emailService->sendOrderShippedEmail(
                    $order->getCustomerEmail(),
                    $customerName,
                    $order->getOrderNumber(),
                    null, // Tracking number - could be extended in the future
                    $orderData
                );
            }
        } catch (\Exception $e) {
            // Log error but don't interrupt the status change process
            error_log('Failed to send order status changed email: ' . $e->getMessage());
        }
    }
}