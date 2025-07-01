<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Domain\Order\Model\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders', name: 'admin_order_')]
#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(): Response
    {
        $orders = $this->orderService->getAllOrders();
        
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{orderNumber}', name: 'details', methods: ['GET'])]
    public function details(string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }
        
        return $this->render('admin/order/details.html.twig', [
            'order' => $order,
        ]);
    }
    
    #[Route('/{orderNumber}/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(Request $request, string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);
        
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }
        
        $newStatus = $request->request->get('status');
        if ($newStatus) {
            try {
                $this->orderService->updateOrderStatus($order, $newStatus);
                $this->addFlash('success', 'Order status updated successfully.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        
        return $this->redirectToRoute('admin_order_details', ['orderNumber' => $orderNumber]);
    }
}
