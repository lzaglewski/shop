<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Cart\Model;

use App\Domain\Cart\Model\Cart;
use App\Domain\Cart\Model\CartItem;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function testCreateCartWithoutUser(): void
    {
        $sessionId = 'test_session_123';
        $cart = new Cart(null, $sessionId);

        $this->assertNull($cart->getUser());
        $this->assertEquals($sessionId, $cart->getSessionId());
        $this->assertInstanceOf(Collection::class, $cart->getItems());
        $this->assertEmpty($cart->getItems());
        $this->assertInstanceOf(\DateTimeInterface::class, $cart->getCreatedAt());
        $this->assertNull($cart->getUpdatedAt());
        $this->assertEquals(0, $cart->getTotalQuantity());
        $this->assertEquals(0.0, $cart->getTotalPrice());
    }

    public function testCreateCartWithUser(): void
    {
        $user = new User('test@example.com', 'password123', 'Test Company', '123456789', UserRole::CLIENT);
        $cart = new Cart($user);

        $this->assertSame($user, $cart->getUser());
        $this->assertNull($cart->getSessionId());
        $this->assertInstanceOf(Collection::class, $cart->getItems());
        $this->assertEmpty($cart->getItems());
    }

    public function testSetters(): void
    {
        $cart = new Cart();
        
        $user = new User('test@example.com', 'password123', 'Test Company');
        $cart->setUser($user);
        $this->assertSame($user, $cart->getUser());
        
        $sessionId = 'new_session_456';
        $cart->setSessionId($sessionId);
        $this->assertEquals($sessionId, $cart->getSessionId());
        
        // Test unsetting user
        $cart->setUser(null);
        $this->assertNull($cart->getUser());
        
        // Test unsetting session ID
        $cart->setSessionId(null);
        $this->assertNull($cart->getSessionId());
    }

    public function testAddItem(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        
        $cart->addItem($item);
        
        $this->assertTrue($cart->getItems()->contains($item));
        $this->assertCount(1, $cart->getItems());
        $this->assertSame($cart, $item->getCart());
        $this->assertNotNull($cart->getUpdatedAt());
    }

    public function testAddItemWithSameProduct(): void
    {
        // Test że dodanie tego samego produktu zwiększa ilość zamiast dodawania nowej pozycji
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item1 = new CartItem($product, 2);
        $item2 = new CartItem($product, 3);
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        // Powinien być tylko jeden element w koszyku
        $this->assertCount(1, $cart->getItems());
        // Ilość pierwszego elementu powinna zostać zwiększona
        $this->assertEquals(5, $item1->getQuantity());
    }

    public function testRemoveItem(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        // Sprawdź, że element jest w koszyku
        $this->assertTrue($cart->getItems()->contains($item));
        
        $cart->removeItem($item);
        
        // Sprawdź, że element został usunięty
        $this->assertFalse($cart->getItems()->contains($item));
        $this->assertCount(0, $cart->getItems());
        $this->assertNull($item->getCart());
        $this->assertNotNull($cart->getUpdatedAt());
    }

    public function testRemoveNonExistentItem(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 1);
        
        // Próba usunięcia elementu, który nie jest w koszyku
        $cart->removeItem($item);
        
        $this->assertCount(0, $cart->getItems());
        $this->assertNull($cart->getUpdatedAt());
    }

    public function testUpdateItemQuantity(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        // Zaktualizuj ilość
        $cart->updateItemQuantity(1, 5);
        $this->assertEquals(5, $item->getQuantity());
        $this->assertNotNull($cart->getUpdatedAt());
    }

    public function testUpdateItemQuantityToZero(): void
    {
        // Test że ustawienie ilości na 0 usuwa element z koszyka
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        $cart->updateItemQuantity(1, 0);
        
        $this->assertFalse($cart->getItems()->contains($item));
        $this->assertCount(0, $cart->getItems());
    }

    public function testUpdateItemQuantityNegative(): void
    {
        // Test że ustawienie ilości na wartość ujemną usuwa element z koszyka
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        $cart->updateItemQuantity(1, -1);
        
        $this->assertFalse($cart->getItems()->contains($item));
        $this->assertCount(0, $cart->getItems());
    }

    public function testUpdateItemQuantityNonExistentProduct(): void
    {
        $cart = new Cart();
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 2);
        $cart->addItem($item);
        
        // Próba aktualizacji produktu o innym ID
        $cart->updateItemQuantity(999, 10);
        
        // Ilość oryginalnego produktu nie powinna się zmienić
        $this->assertEquals(2, $item->getQuantity());
    }

    public function testClear(): void
    {
        $cart = new Cart();
        
        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product1->method('getBasePrice')->willReturn(10.0);
        
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);
        $product2->method('getBasePrice')->willReturn(15.0);
        
        $item1 = new CartItem($product1, 2);
        $item2 = new CartItem($product2, 3);
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        $cart->clear();
        
        $this->assertCount(0, $cart->getItems());
        $this->assertNull($item1->getCart());
        $this->assertNull($item2->getCart());
    }

    public function testGetTotalQuantity(): void
    {
        $cart = new Cart();
        
        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product1->method('getBasePrice')->willReturn(10.0);
        
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);
        $product2->method('getBasePrice')->willReturn(15.0);
        
        $item1 = new CartItem($product1, 2);
        $item2 = new CartItem($product2, 3);
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        $this->assertEquals(5, $cart->getTotalQuantity());
    }

    public function testGetTotalPrice(): void
    {
        $cart = new Cart();
        
        $product1 = $this->createMock(Product::class);
        $product1->method('getId')->willReturn(1);
        $product1->method('getBasePrice')->willReturn(10.0);
        
        $product2 = $this->createMock(Product::class);
        $product2->method('getId')->willReturn(2);
        $product2->method('getBasePrice')->willReturn(15.0);
        
        $item1 = new CartItem($product1, 2); // 2 * 10.0 = 20.0
        $item2 = new CartItem($product2, 3); // 3 * 15.0 = 45.0
        
        $cart->addItem($item1);
        $cart->addItem($item2);
        
        $this->assertEquals(65.0, $cart->getTotalPrice());
    }

    public function testTimestampUpdates(): void
    {
        $cart = new Cart();
        $initialUpdatedAt = $cart->getUpdatedAt();
        
        $this->assertNull($initialUpdatedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $cart->getCreatedAt());
        
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $product->method('getBasePrice')->willReturn(10.0);
        
        $item = new CartItem($product, 1);
        
        // Dodanie elementu powinno zaktualizować timestamp
        $cart->addItem($item);
        $this->assertNotNull($cart->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $cart->getUpdatedAt());
        
        $firstUpdateTime = $cart->getUpdatedAt();
        
        // Symulujemy upływ czasu
        sleep(1); // 1 second delay to ensure different timestamps
        
        // Aktualizacja ilości powinna zaktualizować timestamp
        $cart->updateItemQuantity(1, 2);
        $this->assertGreaterThanOrEqual($firstUpdateTime->getTimestamp(), $cart->getUpdatedAt()->getTimestamp());
    }
}