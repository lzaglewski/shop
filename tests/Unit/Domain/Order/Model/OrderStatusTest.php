<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Model;

use App\Domain\Order\Model\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function testEnumValues(): void
    {
        // Test wszystkich wartości enum
        $this->assertEquals('new', OrderStatus::NEW->value);
        $this->assertEquals('processing', OrderStatus::PROCESSING->value);
        $this->assertEquals('shipped', OrderStatus::SHIPPED->value);
        $this->assertEquals('delivered', OrderStatus::DELIVERED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    public function testEnumCases(): void
    {
        // Test że wszystkie przypadki są dostępne
        $allCases = OrderStatus::cases();
        
        $this->assertCount(5, $allCases);
        $this->assertContains(OrderStatus::NEW, $allCases);
        $this->assertContains(OrderStatus::PROCESSING, $allCases);
        $this->assertContains(OrderStatus::SHIPPED, $allCases);
        $this->assertContains(OrderStatus::DELIVERED, $allCases);
        $this->assertContains(OrderStatus::CANCELLED, $allCases);
    }

    public function testEnumFromValue(): void
    {
        // Test tworzenia enum z wartości string
        $this->assertSame(OrderStatus::NEW, OrderStatus::from('new'));
        $this->assertSame(OrderStatus::PROCESSING, OrderStatus::from('processing'));
        $this->assertSame(OrderStatus::SHIPPED, OrderStatus::from('shipped'));
        $this->assertSame(OrderStatus::DELIVERED, OrderStatus::from('delivered'));
        $this->assertSame(OrderStatus::CANCELLED, OrderStatus::from('cancelled'));
    }

    public function testEnumTryFromValid(): void
    {
        // Test bezpiecznego tworzenia enum z poprawnych wartości
        $this->assertSame(OrderStatus::NEW, OrderStatus::tryFrom('new'));
        $this->assertSame(OrderStatus::PROCESSING, OrderStatus::tryFrom('processing'));
        $this->assertSame(OrderStatus::SHIPPED, OrderStatus::tryFrom('shipped'));
        $this->assertSame(OrderStatus::DELIVERED, OrderStatus::tryFrom('delivered'));
        $this->assertSame(OrderStatus::CANCELLED, OrderStatus::tryFrom('cancelled'));
    }

    public function testEnumTryFromInvalid(): void
    {
        // Test bezpiecznego tworzenia enum z niepoprawnych wartości
        $this->assertNull(OrderStatus::tryFrom('invalid'));
        $this->assertNull(OrderStatus::tryFrom(''));
        $this->assertNull(OrderStatus::tryFrom('NEW')); // Case sensitive
        $this->assertNull(OrderStatus::tryFrom('pending'));
        $this->assertNull(OrderStatus::tryFrom('completed'));
    }

    public function testEnumFromInvalidThrowsException(): void
    {
        // Test że from() rzuca wyjątek dla niepoprawnej wartości
        $this->expectException(\ValueError::class);
        OrderStatus::from('invalid');
    }

    public function testEnumComparison(): void
    {
        // Test porównywania enums
        $status1 = OrderStatus::NEW;
        $status2 = OrderStatus::NEW;
        $status3 = OrderStatus::PROCESSING;

        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function testEnumInArray(): void
    {
        // Test czy enum można używać w tablicach
        $activeStatuses = [OrderStatus::NEW, OrderStatus::PROCESSING, OrderStatus::SHIPPED];
        $finalStatuses = [OrderStatus::DELIVERED, OrderStatus::CANCELLED];

        $this->assertContains(OrderStatus::NEW, $activeStatuses);
        $this->assertContains(OrderStatus::PROCESSING, $activeStatuses);
        $this->assertNotContains(OrderStatus::DELIVERED, $activeStatuses);
        
        $this->assertContains(OrderStatus::DELIVERED, $finalStatuses);
        $this->assertNotContains(OrderStatus::NEW, $finalStatuses);
    }

    public function testEnumSerialization(): void
    {
        // Test serializacji enum do JSON
        $status = OrderStatus::PROCESSING;
        
        $this->assertEquals('processing', $status->value);
        $this->assertEquals('"processing"', json_encode($status));
    }

    public function testEnumInMatch(): void
    {
        // Test użycia enum w match expressions
        $getStatusDescription = function(OrderStatus $status): string {
            return match($status) {
                OrderStatus::NEW => 'Nowe zamówienie',
                OrderStatus::PROCESSING => 'W trakcie realizacji',
                OrderStatus::SHIPPED => 'Wysłane',
                OrderStatus::DELIVERED => 'Dostarczone',
                OrderStatus::CANCELLED => 'Anulowane',
            };
        };

        $this->assertEquals('Nowe zamówienie', $getStatusDescription(OrderStatus::NEW));
        $this->assertEquals('W trakcie realizacji', $getStatusDescription(OrderStatus::PROCESSING));
        $this->assertEquals('Wysłane', $getStatusDescription(OrderStatus::SHIPPED));
        $this->assertEquals('Dostarczone', $getStatusDescription(OrderStatus::DELIVERED));
        $this->assertEquals('Anulowane', $getStatusDescription(OrderStatus::CANCELLED));
    }

    public function testBusinessLogicWithEnum(): void
    {
        // Test logiki biznesowej używającej enum
        $isOrderActive = function(OrderStatus $status): bool {
            return match($status) {
                OrderStatus::NEW, OrderStatus::PROCESSING, OrderStatus::SHIPPED => true,
                OrderStatus::DELIVERED, OrderStatus::CANCELLED => false,
            };
        };

        $this->assertTrue($isOrderActive(OrderStatus::NEW));
        $this->assertTrue($isOrderActive(OrderStatus::PROCESSING));
        $this->assertTrue($isOrderActive(OrderStatus::SHIPPED));
        $this->assertFalse($isOrderActive(OrderStatus::DELIVERED));
        $this->assertFalse($isOrderActive(OrderStatus::CANCELLED));
    }

    public function testCanOrderBeModified(): void
    {
        // Test czy zamówienie może być modyfikowane w zależności od statusu
        $canModify = function(OrderStatus $status): bool {
            return $status === OrderStatus::NEW;
        };

        $this->assertTrue($canModify(OrderStatus::NEW));
        $this->assertFalse($canModify(OrderStatus::PROCESSING));
        $this->assertFalse($canModify(OrderStatus::SHIPPED));
        $this->assertFalse($canModify(OrderStatus::DELIVERED));
        $this->assertFalse($canModify(OrderStatus::CANCELLED));
    }

    public function testStatusProgression(): void
    {
        // Test logicznej progresji statusów
        $getNextValidStatuses = function(OrderStatus $current): array {
            return match($current) {
                OrderStatus::NEW => [OrderStatus::PROCESSING, OrderStatus::CANCELLED],
                OrderStatus::PROCESSING => [OrderStatus::SHIPPED, OrderStatus::CANCELLED],
                OrderStatus::SHIPPED => [OrderStatus::DELIVERED],
                OrderStatus::DELIVERED => [], // Stan końcowy
                OrderStatus::CANCELLED => [], // Stan końcowy
            };
        };

        $this->assertEquals([OrderStatus::PROCESSING, OrderStatus::CANCELLED], $getNextValidStatuses(OrderStatus::NEW));
        $this->assertEquals([OrderStatus::SHIPPED, OrderStatus::CANCELLED], $getNextValidStatuses(OrderStatus::PROCESSING));
        $this->assertEquals([OrderStatus::DELIVERED], $getNextValidStatuses(OrderStatus::SHIPPED));
        $this->assertEquals([], $getNextValidStatuses(OrderStatus::DELIVERED));
        $this->assertEquals([], $getNextValidStatuses(OrderStatus::CANCELLED));
    }
}