<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Cart\CartService;
use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderItem;
use App\Domain\Order\Model\OrderStatus;
use App\Domain\Order\Repository\OrderRepository;
use App\Domain\User\Model\User;

class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly CartService $cartService
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
        
        $orderStatus = match($status) {
            'new' => OrderStatus::NEW,
            'processing' => OrderStatus::PROCESSING,
            'shipped' => OrderStatus::SHIPPED,
            'delivered' => OrderStatus::DELIVERED,
            'cancelled' => OrderStatus::CANCELLED,
            default => throw new \InvalidArgumentException('Invalid order status: ' . $status)
        };
        
        $order->setStatus($orderStatus);
        $this->orderRepository->save($order);
    }
}
