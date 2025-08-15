<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Order\Model\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderShippedEvent extends Event
{
    public const NAME = 'order.shipped';

    public function __construct(
        private readonly Order $order,
        private readonly ?string $trackingNumber = null
    ) {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }
}