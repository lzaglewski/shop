<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Model;

use App\Domain\User\Model\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function testEnumValues(): void
    {
        // Test wszystkich wartości enum
        $this->assertEquals('ROLE_ADMIN', UserRole::ADMIN->value);
        $this->assertEquals('ROLE_CLIENT', UserRole::CLIENT->value);
    }

    public function testEnumCases(): void
    {
        // Test że wszystkie przypadki są dostępne
        $allCases = UserRole::cases();
        
        $this->assertCount(2, $allCases);
        $this->assertContains(UserRole::ADMIN, $allCases);
        $this->assertContains(UserRole::CLIENT, $allCases);
    }

    public function testEnumFromValue(): void
    {
        // Test tworzenia enum z wartości string
        $this->assertSame(UserRole::ADMIN, UserRole::from('ROLE_ADMIN'));
        $this->assertSame(UserRole::CLIENT, UserRole::from('ROLE_CLIENT'));
    }

    public function testEnumTryFromValid(): void
    {
        // Test bezpiecznego tworzenia enum z poprawnych wartości
        $this->assertSame(UserRole::ADMIN, UserRole::tryFrom('ROLE_ADMIN'));
        $this->assertSame(UserRole::CLIENT, UserRole::tryFrom('ROLE_CLIENT'));
    }

    public function testEnumTryFromInvalid(): void
    {
        // Test bezpiecznego tworzenia enum z niepoprawnych wartości
        $this->assertNull(UserRole::tryFrom('ROLE_USER'));
        $this->assertNull(UserRole::tryFrom('ADMIN'));
        $this->assertNull(UserRole::tryFrom('CLIENT'));
        $this->assertNull(UserRole::tryFrom(''));
        $this->assertNull(UserRole::tryFrom('role_admin')); // Case sensitive
        $this->assertNull(UserRole::tryFrom('ROLE_MANAGER'));
    }

    public function testEnumFromInvalidThrowsException(): void
    {
        // Test że from() rzuca wyjątek dla niepoprawnej wartości
        $this->expectException(\ValueError::class);
        UserRole::from('ROLE_USER');
    }

    public function testEnumComparison(): void
    {
        // Test porównywania enums
        $role1 = UserRole::ADMIN;
        $role2 = UserRole::ADMIN;
        $role3 = UserRole::CLIENT;

        $this->assertSame($role1, $role2);
        $this->assertNotSame($role1, $role3);
        $this->assertTrue($role1 === $role2);
        $this->assertFalse($role1 === $role3);
    }

    public function testEnumInArray(): void
    {
        // Test czy enum można używać w tablicach
        $adminRoles = [UserRole::ADMIN];
        $allRoles = [UserRole::ADMIN, UserRole::CLIENT];

        $this->assertContains(UserRole::ADMIN, $adminRoles);
        $this->assertNotContains(UserRole::CLIENT, $adminRoles);
        
        $this->assertContains(UserRole::ADMIN, $allRoles);
        $this->assertContains(UserRole::CLIENT, $allRoles);
    }

    public function testEnumSerialization(): void
    {
        // Test serializacji enum do JSON
        $adminRole = UserRole::ADMIN;
        $clientRole = UserRole::CLIENT;
        
        $this->assertEquals('ROLE_ADMIN', $adminRole->value);
        $this->assertEquals('ROLE_CLIENT', $clientRole->value);
        $this->assertEquals('"ROLE_ADMIN"', json_encode($adminRole));
        $this->assertEquals('"ROLE_CLIENT"', json_encode($clientRole));
    }

    public function testEnumInMatch(): void
    {
        // Test użycia enum w match expressions
        $getRoleDescription = function(UserRole $role): string {
            return match($role) {
                UserRole::ADMIN => 'Administrator systemu',
                UserRole::CLIENT => 'Klient',
            };
        };

        $this->assertEquals('Administrator systemu', $getRoleDescription(UserRole::ADMIN));
        $this->assertEquals('Klient', $getRoleDescription(UserRole::CLIENT));
    }

    public function testBusinessLogicWithEnum(): void
    {
        // Test logiki biznesowej używającej enum
        $hasAdminAccess = function(UserRole $role): bool {
            return $role === UserRole::ADMIN;
        };

        $this->assertTrue($hasAdminAccess(UserRole::ADMIN));
        $this->assertFalse($hasAdminAccess(UserRole::CLIENT));
    }

    public function testCanAccessAdminPanel(): void
    {
        // Test czy użytkownik może dostać się do panelu administracyjnego
        $canAccessAdminPanel = function(UserRole $role): bool {
            return match($role) {
                UserRole::ADMIN => true,
                UserRole::CLIENT => false,
            };
        };

        $this->assertTrue($canAccessAdminPanel(UserRole::ADMIN));
        $this->assertFalse($canAccessAdminPanel(UserRole::CLIENT));
    }

    public function testGetPermissions(): void
    {
        // Test uzyskiwania uprawnień na podstawie roli
        $getPermissions = function(UserRole $role): array {
            return match($role) {
                UserRole::ADMIN => [
                    'view_products',
                    'create_products', 
                    'edit_products',
                    'delete_products',
                    'view_orders',
                    'manage_orders',
                    'view_users',
                    'manage_users',
                    'access_admin_panel',
                    'view_statistics'
                ],
                UserRole::CLIENT => [
                    'view_products',
                    'add_to_cart',
                    'create_orders',
                    'view_own_orders',
                    'edit_profile'
                ],
            };
        };

        $adminPermissions = $getPermissions(UserRole::ADMIN);
        $clientPermissions = $getPermissions(UserRole::CLIENT);

        $this->assertContains('access_admin_panel', $adminPermissions);
        $this->assertContains('manage_users', $adminPermissions);
        $this->assertContains('delete_products', $adminPermissions);
        $this->assertGreaterThan(5, count($adminPermissions));

        $this->assertNotContains('access_admin_panel', $clientPermissions);
        $this->assertNotContains('manage_users', $clientPermissions);
        $this->assertNotContains('delete_products', $clientPermissions);
        $this->assertContains('view_products', $clientPermissions);
        $this->assertContains('add_to_cart', $clientPermissions);
    }

    public function testRoleBasedNavigation(): void
    {
        // Test nawigacji opartej na rolach
        $getNavigationItems = function(UserRole $role): array {
            $commonItems = ['Home', 'Products', 'Contact'];
            
            return match($role) {
                UserRole::ADMIN => array_merge($commonItems, [
                    'Admin Panel',
                    'User Management',
                    'Product Management',
                    'Order Management',
                    'Statistics'
                ]),
                UserRole::CLIENT => array_merge($commonItems, [
                    'My Account',
                    'My Orders',
                    'Shopping Cart'
                ]),
            };
        };

        $adminNav = $getNavigationItems(UserRole::ADMIN);
        $clientNav = $getNavigationItems(UserRole::CLIENT);

        // Wspólne elementy
        $this->assertContains('Home', $adminNav);
        $this->assertContains('Products', $adminNav);
        $this->assertContains('Home', $clientNav);
        $this->assertContains('Products', $clientNav);

        // Specyficzne dla administratora
        $this->assertContains('Admin Panel', $adminNav);
        $this->assertContains('User Management', $adminNav);
        
        // Specyficzne dla klienta
        $this->assertContains('My Account', $clientNav);
        $this->assertContains('Shopping Cart', $clientNav);

        // Sprawdź że nie ma przenikania uprawnień
        $this->assertNotContains('Admin Panel', $clientNav);
        $this->assertNotContains('Shopping Cart', $adminNav);
    }

    public function testRoleBasedPricing(): void
    {
        // Test cen opartych na rolach (konceptualny - pokazuje użycie enum w logice biznesowej)
        $getPriceMultiplier = function(UserRole $role): float {
            return match($role) {
                UserRole::ADMIN => 0.0, // Admini mają darmowy dostęp
                UserRole::CLIENT => 1.0, // Klienci płacą pełną cenę
            };
        };

        $this->assertEquals(0.0, $getPriceMultiplier(UserRole::ADMIN));
        $this->assertEquals(1.0, $getPriceMultiplier(UserRole::CLIENT));
    }

    public function testSymfonySecurityRoleCompatibility(): void
    {
        // Test kompatybilności z systemem ról Symfony Security
        $getSymfonyRoles = function(UserRole $role): array {
            return [$role->value];
        };

        $adminRoles = $getSymfonyRoles(UserRole::ADMIN);
        $clientRoles = $getSymfonyRoles(UserRole::CLIENT);

        $this->assertEquals(['ROLE_ADMIN'], $adminRoles);
        $this->assertEquals(['ROLE_CLIENT'], $clientRoles);

        // Test że wartości są zgodne z konwencją Symfony
        $this->assertStringStartsWith('ROLE_', UserRole::ADMIN->value);
        $this->assertStringStartsWith('ROLE_', UserRole::CLIENT->value);
    }

    public function testRoleHierarchy(): void
    {
        // Test hierarchii ról (konceptualny)
        $hasRoleOrHigher = function(UserRole $userRole, UserRole $requiredRole): bool {
            $hierarchy = [
                UserRole::CLIENT->value => 1,
                UserRole::ADMIN->value => 2,
            ];
            
            return $hierarchy[$userRole->value] >= $hierarchy[$requiredRole->value];
        };

        // Admin ma dostęp do wszystkiego
        $this->assertTrue($hasRoleOrHigher(UserRole::ADMIN, UserRole::CLIENT));
        $this->assertTrue($hasRoleOrHigher(UserRole::ADMIN, UserRole::ADMIN));

        // Klient ma dostęp tylko do swoich uprawnień
        $this->assertTrue($hasRoleOrHigher(UserRole::CLIENT, UserRole::CLIENT));
        $this->assertFalse($hasRoleOrHigher(UserRole::CLIENT, UserRole::ADMIN));
    }
}