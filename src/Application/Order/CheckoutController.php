<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Cart\CartService;
use App\Domain\User\Model\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkout', name: 'checkout_')]
#[IsGranted('ROLE_CLIENT')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly CartService  $cartService,
        private readonly OrderService $orderService
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cartService->getCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty. Please add some products before checkout.');
            return $this->redirectToRoute('product_list');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'user' => $user,
        ]);
    }

    #[Route('/place-order', name: 'place_order', methods: ['POST'])]
    public function placeOrder(Request $request): Response
    {
        $cart = $this->cartService->getCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty. Please add some products before checkout.');
            return $this->redirectToRoute('product_list');
        }

        // Get form data
        $customerEmail = $request->request->get('email');
        $customerCompanyName = $request->request->get('company_name');
        $customerTaxId = $request->request->get('tax_id');
        $shippingAddress = $request->request->get('shipping_address');
        $billingAddress = $request->request->get('billing_address');
        $notes = $request->request->get('notes');

        // Validate required fields
        if (!$customerEmail || !$customerCompanyName || !$shippingAddress || !$billingAddress) {
            $this->addFlash('error', 'Please fill in all required fields.');
            return $this->redirectToRoute('checkout_index');
        }

        try {
            $order = $this->orderService->createOrderFromCart(
                $customerEmail,
                $customerCompanyName,
                $customerTaxId,
                $shippingAddress,
                $billingAddress,
                $notes,
                $this->getUser()
            );

            return $this->redirectToRoute('checkout_success', ['orderNumber' => $order->getOrderNumber()]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while processing your order: ' . $e->getMessage());
            return $this->redirectToRoute('checkout_index');
        }
    }

    #[Route('/success/{orderNumber}', name: 'success', methods: ['GET'])]
    public function success(string $orderNumber): Response
    {
        $order = $this->orderService->getOrderByNumber($orderNumber);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        $currentUser = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && $order->getUser() !== $currentUser) {
            throw $this->createAccessDeniedException('You do not have access to this order');
        }

        return $this->render('checkout/success.html.twig', [
            'order' => $order,
        ]);
    }
}
