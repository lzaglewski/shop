<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\User\Model;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testCreateUser(): void
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
        
        $this->assertEquals($email, $user->getUserIdentifier());
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
