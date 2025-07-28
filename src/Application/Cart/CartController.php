<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Domain\Product\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ProductRepository $productRepository
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
            $this->addFlash('success', 'Product added to cart successfully.');
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
            $this->addFlash('success', 'Cart updated successfully.');
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
            $this->addFlash('success', 'Item removed from cart.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clearCart();
        $this->addFlash('success', 'Cart cleared successfully.');
        
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/ajax-add', name: 'ajax_add', methods: ['POST'])]
    public function ajaxAdd(Request $request): JsonResponse
    {
        $productId = (int) $request->request->get('product_id');
        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity <= 0) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid quantity']);
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
                'message' => 'Product added to cart successfully',
                'cartProductIds' => $cartProductIds,
                'itemCount' => $this->cartService->getItemCount()
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error adding product to cart']);
        }
    }
    
    public function cartItemCount(): Response
    {
        $itemCount = $this->cartService->getItemCount();
        
        return new Response((string) $itemCount);
    }
}
