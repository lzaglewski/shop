<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Domain\Product\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        
        try {
            $this->cartService->addToCart($id, $quantity);
            $this->addFlash('success', 'cart.product_added_successfully');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('product_index'));
    }

    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        
        try {
            $this->cartService->updateQuantity($id, $quantity);
            $this->addFlash('success', 'cart.cart_updated_successfully');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/remove/{id}', name: 'remove', methods: ['POST'])]
    public function remove(int $id): Response
    {
        try {
            $this->cartService->removeItem($id);
            $this->addFlash('success', 'cart.item_removed_from_cart');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clearCart();
        $this->addFlash('success', 'cart.cart_cleared_successfully');
        
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/ajax-add', name: 'ajax_add', methods: ['POST'])]
    public function ajaxAdd(Request $request): JsonResponse
    {
        $productId = (int) $request->request->get('product_id');
        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity <= 0) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('cart.invalid_quantity')]);
        }

        try {
            $this->cartService->addToCart($productId, $quantity);
            
            // Get updated cart info for response
            $cart = $this->cartService->getCart();
            $cartProductIds = [];
            foreach ($cart->getItems() as $cartItem) {
                $cartProductIds[] = $cartItem->getProduct()->getId();
            }
            
            return new JsonResponse([
                'success' => true, 
                'message' => $this->translator->trans('cart.product_added_successfully'),
                'cartProductIds' => $cartProductIds,
                'itemCount' => $this->cartService->getItemCount()
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('cart.error_adding_product_to_cart')]);
        }
    }
    
    public function cartItemCount(): Response
    {
        $itemCount = $this->cartService->getItemCount();
        
        return new Response((string) $itemCount);
    }
}
