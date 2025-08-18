<?php

declare(strict_types=1);

namespace App\Application\Order;

use App\Application\Cart\CartService;
use App\Application\Form\CheckoutType;
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

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $cart = $this->cartService->getCart();

        if ($cart->getItems()->isEmpty()) {
            $this->addFlash('error', 'Your cart is empty. Please add some products before checkout.');
            return $this->redirectToRoute('product_list');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        // Tworzenie formularza
        $form = $this->createForm(CheckoutType::class);

        // Wypełnienie formularza danymi użytkownika jeśli jest zalogowany
        if ($user) {
            $form->setData([
                'email' => $user->getEmail(),
                'contactNumber' => $user->getContactNumber(),
                'deliveryStreet' => $user->getDeliveryStreet(),
                'deliveryPostalCode' => $user->getDeliveryPostalCode(),
                'deliveryCity' => $user->getDeliveryCity(),
                'billingCompanyName' => $user->getBillingCompanyName(),
                'billingStreet' => $user->getBillingStreet(),
                'billingPostalCode' => $user->getBillingPostalCode(),
                'billingCity' => $user->getBillingCity(),
                'billingTaxId' => $user->getBillingTaxId(),
            ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            $sameAsBilling = isset($data['sameAsBilling']) && $data['sameAsBilling'];
            
            // Jeśli checkbox "sameAsBilling" jest zaznaczony, kopiuj adres rozliczeniowy do dostawy
            if ($sameAsBilling) {
                $data['deliveryStreet'] = $data['billingStreet'];
                $data['deliveryPostalCode'] = $data['billingPostalCode'];
                $data['deliveryCity'] = $data['billingCity'];
            } else {
                // Walidacja pól delivery gdy checkbox nie jest zaznaczony
                if (empty($data['deliveryStreet']) || empty($data['deliveryPostalCode']) || empty($data['deliveryCity'])) {
                    $this->addFlash('error', 'Proszę wypełnić wszystkie wymagane pola adresu dostawy.');
                    return $this->render('checkout/index.html.twig', [
                        'cart' => $cart,
                        'user' => $user,
                        'checkoutForm' => $form->createView(),
                    ]);
                }
            }

            try {
                $order = $this->orderService->createOrderFromCart(
                    $data['email'],
                    '', // companyName - nie używamy
                    null, // taxId - nie używamy
                    json_encode([
                        'street' => $data['deliveryStreet'],
                        'postalCode' => $data['deliveryPostalCode'],
                        'city' => $data['deliveryCity'],
                        'contactNumber' => $data['contactNumber'] ?? null,
                    ]),
                    json_encode([
                        'companyName' => $data['billingCompanyName'] ?? '',
                        'street' => $data['billingStreet'],
                        'postalCode' => $data['billingPostalCode'],
                        'city' => $data['billingCity'],
                        'taxId' => $data['billingTaxId'] ?? '',
                    ]),
                    $data['notes'] ?? '',
                    $user
                );

                return $this->redirectToRoute('checkout_success', ['orderNumber' => $order->getOrderNumber()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while processing your order: ' . $e->getMessage());
            }
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'user' => $user,
            'checkoutForm' => $form->createView(),
        ]);
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
