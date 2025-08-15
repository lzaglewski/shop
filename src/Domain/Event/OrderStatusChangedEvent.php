<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderStatus;
use Symfony\Contracts\EventDispatcher\Event;

class OrderStatusChangedEvent extends Event
{
    public const NAME = 'order.status_changed';

    public function __construct(
        private readonly Order $order,
        private readonly OrderStatus $previousStatus,
        private readonly OrderStatus $newStatus
    ) {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getPreviousStatus(): OrderStatus
    {
        return $this->previousStatus;
    }

    public function getNewStatus(): OrderStatus
    {
        return $this->newStatus;
    }
}