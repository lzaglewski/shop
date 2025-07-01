<?php

declare(strict_types=1);

namespace App\Application\Cart;

use App\Application\Service\ClientPriceService;
use App\Domain\Cart\Model\Cart;
use App\Domain\Cart\Model\CartItem;
use App\Domain\Cart\Repository\CartRepository;
use App\Domain\Product\Model\Product;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\User\Model\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class CartService
{
    private ?Cart $cart = null;
    private ?string $sessionId = null;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly ProductRepository $productRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly ClientPriceService $clientPriceService
    ) {
        $session = $this->requestStack->getSession();
        $this->sessionId = $session->getId();
    }

    public function getCart(): Cart
    {
        if ($this->cart !== null) {
            return $this->cart;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        // If user is logged in, try to find their cart
        if ($user) {
            $this->cart = $this->cartRepository->findByUser($user);
            
            // If user has no cart but there's a session cart, associate it with the user
            if ($this->cart === null) {
                $sessionCart = $this->cartRepository->findBySessionId($this->sessionId);
                
                if ($sessionCart) {
                    $sessionCart->setUser($user);
                    $sessionCart->setSessionId(null);
                    $this->cartRepository->save($sessionCart);
                    $this->cart = $sessionCart;
                }
            }
        } else {
            // For anonymous users, try to find cart by session ID
            $this->cart = $this->cartRepository->findBySessionId($this->sessionId);
        }

        // If no cart exists yet, create a new one
        if ($this->cart === null) {
            $this->cart = new Cart($user, $user ? null : $this->sessionId);
            $this->cartRepository->save($this->cart);
        }

        return $this->cart;
    }

    public function addToCart(int $productId, int $quantity = 1): void
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $cart = $this->getCart();
        
        // Pobierz cenę dla klienta, jeśli jest zalogowany
        $price = $product->getBasePrice();
        $user = $this->security->getUser();
        
        if ($user instanceof User) {
            $price = $this->clientPriceService->getClientPrice($user, $product);
        }
        
        $cartItem = new CartItem($product, $quantity);
        $cartItem->setPrice($price);
        $cart->addItem($cartItem);
        
        $this->cartRepository->save($cart);
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();
        
        // Jeśli ilość jest większa od 0, upewnij się, że cena jest aktualna
        if ($quantity > 0) {
            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new \InvalidArgumentException('Product not found');
            }
            
            // Pobierz cenę dla klienta, jeśli jest zalogowany
            $price = $product->getBasePrice();
            $user = $this->security->getUser();
            
            if ($user instanceof User) {
                $price = $this->clientPriceService->getClientPrice($user, $product);
            }
            
            // Zaktualizuj cenę dla istniejącego elementu koszyka
            foreach ($cart->getItems() as $item) {
                if ($item->getProduct()->getId() === $productId) {
                    $item->setPrice($price);
                    break;
                }
            }
        }
        
        $cart->updateItemQuantity($productId, $quantity);
        $this->cartRepository->save($cart);
    }

    public function removeItem(int $productId): void
    {
        $this->updateQuantity($productId, 0);
    }

    public function clearCart(): void
    {
        $cart = $this->getCart();
        $cart->clear();
        
        $this->cartRepository->save($cart);
    }

    public function getItemCount(): int
    {
        return $this->getCart()->getTotalQuantity();
    }

    public function getTotal(): float
    {
        return $this->getCart()->getTotalPrice();
    }

    public function mergeCartsAfterLogin(User $user): void
    {
        $sessionCart = $this->cartRepository->findBySessionId($this->sessionId);
        $userCart = $this->cartRepository->findByUser($user);

        if ($sessionCart && $userCart) {
            // Merge the anonymous cart with the user's cart
            $this->cartRepository->mergeAnonymousCartWithUserCart($sessionCart, $userCart);
            $this->cart = $userCart;
        } elseif ($sessionCart) {
            // Associate the session cart with the user
            $sessionCart->setUser($user);
            $sessionCart->setSessionId(null);
            $this->cartRepository->save($sessionCart);
            $this->cart = $sessionCart;
        }
    }
}
