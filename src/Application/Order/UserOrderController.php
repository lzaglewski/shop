<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\User\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account/orders', name: 'user_')]
#[IsGranted('ROLE_CLIENT')]
class UserOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    #[Route('', name: 'orders', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $this->orderService->getUserOrders($user);
        
        return $this->render('order/user_orders.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{orderNumber}', name: 'order_details', methods: ['GET'])]
    public function details(string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        /** @var User $user */
        $user = $this->getUser();
        
        // Check if the order belongs to the current user
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to view this order');
        }
        
        return $this->render('order/order_details.html.twig', [
            'order' => $order,
        ]);
    }
}
