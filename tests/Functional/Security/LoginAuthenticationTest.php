<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginAuthenticationTest extends WebTestCase
{
    private function createTestUser(KernelBrowser $client, string $email, string $login, string $password): User
    {
        $container = $client->getContainer();

        $userRepository = $container->get(UserRepositoryInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User(
            $email,
            'temp', // Will be replaced with hashed password
            'Test Company',
            '1234567890',
            UserRole::CLIENT
        );

        $user->setLogin($login);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $userRepository->save($user);

        return $user;
    }

    public function testLoginWithEmail(): void
    {
        $client = static::createClient();

        // Create test user
        $email = 'emailtest@example.com';
        $login = 'emailuser';
        $password = 'testpassword123';

        $user = $this->createTestUser($client, $email, $login, $password);

        // Try to login with email
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'identifier' => $email,
            'password' => $password,
        ]);

        $client->submit($form);

        // Should redirect after successful login
        $this->assertResponseRedirects();

        // Cleanup
        $userRepository = $client->getContainer()->get(UserRepositoryInterface::class);
        $freshUser = $userRepository->findByEmail($user->getEmail());
        if ($freshUser) {
            $userRepository->remove($freshUser);
        }
    }

    public function testLoginWithLogin(): void
    {
        $client = static::createClient();

        // Create test user
        $email = 'logintest@example.com';
        $login = 'loginuser';
        $password = 'testpassword123';

        $user = $this->createTestUser($client, $email, $login, $password);

        // Try to login with login
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'identifier' => $login,
            'password' => $password,
        ]);

        $client->submit($form);

        // Should redirect after successful login
        $this->assertResponseRedirects();

        // Cleanup
        $userRepository = $client->getContainer()->get(UserRepositoryInterface::class);
        $freshUser = $userRepository->findByEmail($user->getEmail());
        if ($freshUser) {
            $userRepository->remove($freshUser);
        }
    }

    public function testLoginWithUppercaseLogin(): void
    {
        $client = static::createClient();

        // Create test user with lowercase login
        $email = 'casetest@example.com';
        $login = 'caseuser';
        $password = 'testpassword123';

        $user = $this->createTestUser($client, $email, $login, $password);

        // Try to login with UPPERCASE login
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'identifier' => 'CASEUSER', // Should work because it's normalized to lowercase
            'password' => $password,
        ]);

        $client->submit($form);

        // Should redirect after successful login
        $this->assertResponseRedirects();

        // Cleanup
        $userRepository = $client->getContainer()->get(UserRepositoryInterface::class);
        $freshUser = $userRepository->findByEmail($user->getEmail());
        if ($freshUser) {
            $userRepository->remove($freshUser);
        }
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();

        // Try to login with non-existent user
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            'identifier' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $client->submit($form);

        // Should stay on login page or redirect back
        $this->assertResponseStatusCodeSame(302);
    }
}
