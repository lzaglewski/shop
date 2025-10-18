<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Domain\CookieConsent\Model\CookieConsent;
use App\Domain\CookieConsent\Model\CookieConsentLog;
use App\Domain\CookieConsent\Repository\CookieConsentRepositoryInterface;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CookieConsentControllerTest extends WebTestCase
{
    public function testGetConsentsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->jsonRequest('GET', '/api/consents');

        $this->assertResponseStatusCodeSame(302);
    }

    public function testGetConsentsReturnsDefaultsForNewUser(): void
    {
        [$client, $user] = $this->createAuthenticatedClient();

        try {
            $client->jsonRequest('GET', '/api/consents');

            $this->assertResponseIsSuccessful();

            $payload = $this->decodeResponse($client);

            $this->assertArrayHasKey('data', $payload);
            $this->assertSame(
                [
                    'necessary' => true,
                    'analytics' => false,
                    'marketing' => false,
                    'personalization' => false,
                ],
                $payload['data']
            );

            /** @var CookieConsentRepositoryInterface $consentRepository */
            $consentRepository = $client->getContainer()->get(CookieConsentRepositoryInterface::class);
            $consent = $consentRepository->findByUser($user);
            $this->assertInstanceOf(CookieConsent::class, $consent);
        } finally {
            $this->cleanup($client, $user);
        }
    }

    public function testUpdateConsentsPersistsPreferences(): void
    {
        [$client, $user] = $this->createAuthenticatedClient();

        try {
            $client->jsonRequest('PATCH', '/api/consents', [
                'analytics' => true,
                'marketing' => false,
                'personalization' => true,
            ]);

            $this->assertResponseIsSuccessful();

            $payload = $this->decodeResponse($client);

            $this->assertTrue($payload['data']['analytics']);
            $this->assertFalse($payload['data']['marketing']);
            $this->assertTrue($payload['data']['personalization']);

            /** @var CookieConsentRepositoryInterface $consentRepository */
            $consentRepository = $client->getContainer()->get(CookieConsentRepositoryInterface::class);
            $consent = $consentRepository->findByUser($user);

            $this->assertInstanceOf(CookieConsent::class, $consent);
            $this->assertTrue($consent->getAnalytics());
            $this->assertFalse($consent->getMarketing());
            $this->assertTrue($consent->getPersonalization());

            /** @var EntityManagerInterface $entityManager */
            $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
            $logCount = $entityManager->getRepository(CookieConsentLog::class)->count(['user' => $user]);

            $this->assertGreaterThanOrEqual(1, $logCount);
        } finally {
            $this->cleanup($client, $user);
        }
    }

    public function testMigrateCreatesConsentRecord(): void
    {
        [$client, $user] = $this->createAuthenticatedClient();

        try {
            $client->jsonRequest('POST', '/api/consents/migrate', [
                'analytics' => true,
                'marketing' => true,
                'personalization' => false,
            ]);

            $this->assertResponseStatusCodeSame(201);

            $payload = $this->decodeResponse($client);

            $this->assertTrue($payload['data']['analytics']);
            $this->assertTrue($payload['data']['marketing']);
            $this->assertFalse($payload['data']['personalization']);

            /** @var CookieConsentRepositoryInterface $consentRepository */
            $consentRepository = $client->getContainer()->get(CookieConsentRepositoryInterface::class);
            $consent = $consentRepository->findByUser($user);

            $this->assertInstanceOf(CookieConsent::class, $consent);
            $this->assertTrue($consent->getAnalytics());
            $this->assertTrue($consent->getMarketing());
        } finally {
            $this->cleanup($client, $user);
        }
    }

    /**
     * @return array{0: KernelBrowser, 1: User}
     */
    private function createAuthenticatedClient(): array
    {
        $client = static::createClient();
        $container = $client->getContainer();

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = sprintf('consent_%s@example.com', uniqid('', true));
        $user = new User(
            $email,
            'temp',
            'Consent Tester',
            null,
            UserRole::CLIENT
        );

        $hashedPassword = $passwordHasher->hashPassword($user, 'StrongPassword123!');
        $user->setPassword($hashedPassword);

        $userRepository->save($user);
        $client->loginUser($user);

        return [$client, $user];
    }

    private function cleanup(KernelBrowser $client, User $user): void
    {
        $container = $client->getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);

        $consent = $entityManager->getRepository(CookieConsent::class)->findOneBy(['user' => $user]);
        if ($consent instanceof CookieConsent) {
            $entityManager->remove($consent);
        }

        $logs = $entityManager->getRepository(CookieConsentLog::class)->findBy(['user' => $user]);
        foreach ($logs as $log) {
            $entityManager->remove($log);
        }

        $entityManager->flush();
        $userRepository->remove($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(KernelBrowser $client): array
    {
        $responseContent = $client->getResponse()->getContent();

        $this->assertNotFalse($responseContent);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
