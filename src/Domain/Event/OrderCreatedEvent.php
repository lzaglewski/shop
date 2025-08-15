<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Order\Model\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderCreatedEvent extends Event
{
    public const NAME = 'order.created';

    public function __construct(
        private readonly Order $order
    ) {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}