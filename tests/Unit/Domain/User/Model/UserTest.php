<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Model;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUserWithEmail(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $companyName = 'Test Company';
        $taxId = '123456789';
        $role = UserRole::CLIENT;

        $user = new User($email, $password, $companyName, $taxId, $role);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($companyName, $user->getCompanyName());
        $this->assertEquals($taxId, $user->getTaxId());
        $this->assertEquals($role, $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertEmpty($user->getClientPrices());
    }

    public function testCreateUserWithLogin(): void
    {
        $user = new User(
            null,
            'password123',
            'Test Company',
            '123456789',
            UserRole::CLIENT,
            'testuser'
        );

        $this->assertNull($user->getEmail());
        $this->assertEquals('testuser', $user->getLogin());
        $this->assertEquals('password123', $user->getPassword());
        $this->assertEquals('Test Company', $user->getCompanyName());
        $this->assertEquals('123456789', $user->getTaxId());
        $this->assertEquals(UserRole::CLIENT, $user->getRole());
        $this->assertTrue($user->isActive());
        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testCreateUserWithBothEmailAndLogin(): void
    {
        $user = new User(
            'test@example.com',
            'password123',
            'Test Company',
            '123456789',
            UserRole::CLIENT,
            'testuser'
        );

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('testuser', $user->getLogin());
        // Login should take priority in getUserIdentifier
        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testCreateUserWithoutEmailAndLoginThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either email or login must be provided');

        new User(
            null,
            'password123',
            'Test Company',
            '123456789',
            UserRole::CLIENT,
            null
        );
    }

    public function testUserRoles(): void
    {
        $user = new User('client@example.com', 'password', 'Client Company', null, UserRole::CLIENT);
        $this->assertFalse($user->isAdmin());
        $this->assertEquals(['ROLE_CLIENT'], $user->getRoles());

        $adminUser = new User('admin@example.com', 'password', 'Admin Company', null, UserRole::ADMIN);
        $this->assertTrue($adminUser->isAdmin());
        $this->assertEquals(['ROLE_ADMIN'], $adminUser->getRoles());
    }

    public function testUserIdentifier(): void
    {
        $email = 'test@example.com';
        $user = new User($email, 'password', 'Test Company');

        // User identifier defaults to email when login is not set
        $this->assertEquals($email, $user->getUserIdentifier());

        // User identifier is login when login is set
        $user->setLogin('testuser');
        $this->assertEquals('testuser', $user->getUserIdentifier());
    }

    public function testLoginGetterAndSetter(): void
    {
        $user = new User('test@example.com', 'password', 'Test Company');

        // Initially login is null
        $this->assertNull($user->getLogin());

        // Set login
        $user->setLogin('TestUser');
        $this->assertEquals('testuser', $user->getLogin()); // Should be normalized to lowercase

        // Login is trimmed
        $user->setLogin('  spacedlogin  ');
        $this->assertEquals('spacedlogin', $user->getLogin());
    }

    public function testCannotRemoveLoginWhenEmailIsNotSet(): void
    {
        $user = new User(null, 'password', 'Test Company', null, UserRole::CLIENT, 'testuser');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove login when email is not set');

        $user->setLogin(null);
    }

    public function testCannotRemoveEmailWhenLoginIsNotSet(): void
    {
        $user = new User('test@example.com', 'password', 'Test Company');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot remove email when login is not set');

        $user->setEmail(null);
    }

    public function testLoginNormalization(): void
    {
        $user = new User('test@example.com', 'password', 'Test Company');

        // Uppercase is converted to lowercase
        $user->setLogin('UPPERCASE');
        $this->assertEquals('uppercase', $user->getLogin());

        // Mixed case is converted to lowercase
        $user->setLogin('MiXeDCaSe');
        $this->assertEquals('mixedcase', $user->getLogin());

        // Whitespace is trimmed
        $user->setLogin('  trimmed  ');
        $this->assertEquals('trimmed', $user->getLogin());
    }

    public function testSetters(): void
    {
        $user = new User('initial@example.com', 'initial', 'Initial Company');
        
        $newEmail = 'updated@example.com';
        $user->setEmail($newEmail);
        $this->assertEquals($newEmail, $user->getEmail());
        
        $newPassword = 'newpassword';
        $user->setPassword($newPassword);
        $this->assertEquals($newPassword, $user->getPassword());
        
        $newCompanyName = 'Updated Company';
        $user->setCompanyName($newCompanyName);
        $this->assertEquals($newCompanyName, $user->getCompanyName());
        
        $newTaxId = '987654321';
        $user->setTaxId($newTaxId);
        $this->assertEquals($newTaxId, $user->getTaxId());
        
        $user->setRole(UserRole::ADMIN);
        $this->assertEquals(UserRole::ADMIN, $user->getRole());
        
        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }
}
