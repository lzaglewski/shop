<?php

declare(strict_types=1);

namespace App\Application\Email;

use App\Domain\Order\Model\Order;
use App\Domain\Order\Model\OrderStatus;

class OrderEmailDataResolver
{
    public function resolveOrderData(Order $order): array
    {
        $data = [
            'orderNumber' => $order->getOrderNumber(),
            'customerEmail' => $order->getCustomerEmail(),
            'customerCompanyName' => $order->getCustomerCompanyName(),
            'customerTaxId' => $order->getCustomerTaxId(),
            'shippingAddress' => $order->getShippingAddress(),
            'shippingAddressData' => $order->getShippingAddressData(),
            'billingAddress' => $order->getBillingAddress(),
            'billingAddressData' => $order->getBillingAddressData(),
            'notes' => $order->getNotes(),
            'totalAmount' => $order->getTotalAmount(),
            'status' => $this->getStatusDisplayName($order->getStatus()),
            'statusKey' => $order->getStatus()->value,
            'items' => $this->resolveOrderItems($order),
            'createdAt' => $order->getCreatedAt(),
            'updatedAt' => $order->getUpdatedAt(),
            'user' => $order->getUser() ? [
                'email' => $order->getUser()->getEmail(),
                'companyName' => $order->getUser()->getCompanyName()
            ] : null
        ];

        // Add ID only if it's available (order has been persisted)
        try {
            $data['id'] = $order->getId();
            if ($order->getUser()) {
                $data['user']['id'] = $order->getUser()->getId();
            }
        } catch (\Error $e) {
            // ID not available - entity not persisted yet
        }

        return $data;
    }

    public function getCustomerName(Order $order): string
    {
        if ($order->getUser()) {
            return $order->getUser()->getCompanyName();
        }
        
        return $order->getCustomerCompanyName();
    }

    public function getStatusDisplayName(OrderStatus $status): string
    {
        return match($status) {
            OrderStatus::NEW => 'Nowe',
            OrderStatus::PROCESSING => 'W trakcie realizacji',
            OrderStatus::SHIPPED => 'Wysłane',
            OrderStatus::DELIVERED => 'Dostarczone',
            OrderStatus::CANCELLED => 'Anulowane',
        };
    }

    private function resolveOrderItems(Order $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'productName' => $item->getProduct()->getName(),
                'productSku' => $item->getProduct()->getSku(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'subtotal' => $item->getSubtotal()
            ];
        }
        
        return $items;
    }
}