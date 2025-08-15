<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Cart\CartService;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Event\OrderStatusChangedEvent;
use App\Domain\Event\OrderShippedEvent;
use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Order\Model\OrderStatus;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\User\Model\User;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function createOrderFromCart(
        string $customerEmail,
        string $customerCompanyName,
        ?string $customerTaxId,
        string $shippingAddress,
        string $billingAddress,
        ?string $notes,
        ?User $user = null
    ): Order {
        $cart = $this->cartService->getCart();
        
        if ($cart->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Cannot create order from empty cart');
        }

        $order = new Order(
            $customerEmail,
            $customerCompanyName,
            $customerTaxId,
            $shippingAddress,
            $billingAddress,
            $notes,
            $user
        );

        // Convert cart items to order items
        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $orderItem = new OrderItem(
                $product,
                $cartItem->getQuantity(),
                $cartItem->getPrice()
            );
            $order->addItem($orderItem);
        }

        // Save the order
        $this->orderRepository->save($order);
        
        // Dispatch order created event
        $this->eventDispatcher->dispatch(new OrderCreatedEvent($order), OrderCreatedEvent::NAME);
        
        // Clear the cart after successful order creation
        $this->cartService->clearCart();

        return $order;
    }

    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->orderRepository->findByOrderNumber($orderNumber);
    }

    public function getUserOrders(User $user): array
    {
        return $this->orderRepository->findByUser($user);
    }
    
    public function getAllOrders(): array
    {
        return $this->orderRepository->findAll();
    }
    
    public function updateOrderStatus(Order $order, string $status): void
    {
        $validStatuses = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid order status: ' . $status);
        }
        
        $previousStatus = $order->getStatus();
        
        $orderStatus = match($status) {
            'new' => OrderStatus::NEW,
            'processing' => OrderStatus::PROCESSING,
            'shipped' => OrderStatus::SHIPPED,
            'delivered' => OrderStatus::DELIVERED,
            'cancelled' => OrderStatus::CANCELLED,
            default => throw new \InvalidArgumentException('Invalid order status: ' . $status)
        };
        
        // Only update and dispatch event if status actually changed
        if ($previousStatus !== $orderStatus) {
            $order->setStatus($orderStatus);
            $this->orderRepository->save($order);
            
            // Dispatch status changed event
            $this->eventDispatcher->dispatch(
                new OrderStatusChangedEvent($order, $previousStatus, $orderStatus),
                OrderStatusChangedEvent::NAME
            );
            
            // If status changed to shipped, also dispatch shipped event
            if ($orderStatus === OrderStatus::SHIPPED) {
                $this->eventDispatcher->dispatch(
                    new OrderShippedEvent($order),
                    OrderShippedEvent::NAME
                );
            }
        }
    }
}
