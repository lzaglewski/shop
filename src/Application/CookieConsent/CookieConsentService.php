<?php

declare(strict_types=1);

namespace App\Application\CookieConsent;

use App\Domain\CookieConsent\Model\CookieConsent;
use App\Domain\CookieConsent\Model\CookieConsentLog;
use App\Domain\CookieConsent\Repository\CookieConsentLogRepositoryInterface;
use App\Domain\CookieConsent\Repository\CookieConsentRepositoryInterface;
use App\Domain\User\Model\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CookieConsentService
{
    public const CATEGORIES = ['necessary', 'analytics', 'personalization'];

    public function __construct(
        private CookieConsentRepositoryInterface $consentRepository,
        private CookieConsentLogRepositoryInterface $logRepository,
        #[Autowire('%kernel.secret%')] private string $hashSecret,
    ) {
    }

    public function getPreferencesForUser(User $user): array
    {
        $consent = $this->consentRepository->findByUser($user);

        if (!$consent instanceof CookieConsent) {
            $consent = new CookieConsent($user);
            $this->consentRepository->save($consent);
        }

        return $consent->getPreferences();
    }

    public function savePreferencesForUser(
        User $user,
        array $preferences,
        string $action = 'update',
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $normalized = $this->normalizePreferences($preferences);

        $consent = $this->consentRepository->findByUser($user);
        if (!$consent instanceof CookieConsent) {
            $consent = new CookieConsent(
                $user,
                $normalized['analytics'],
                $normalized['personalization'],
            );
        } else {
            $consent->updateFromPreferences($normalized);
        }

        $this->consentRepository->save($consent);

        $log = new CookieConsentLog(
            $normalized,
            $action,
            $user,
            $this->hashIp($ipAddress),
            $this->truncateUserAgent($userAgent),
        );

        $this->logRepository->save($log);

        return $consent->getPreferences();
    }

    /**
     * @return array{necessary: bool, analytics: bool, personalization: bool}
     */
    public function getDefaultPreferences(): array
    {
        return [
            'necessary' => true,
            'analytics' => false,
            'personalization' => false,
        ];
    }

    /**
     * @param array<string, mixed> $preferences
     * @return array{necessary: bool, analytics: bool, personalization: bool}
     */
    private function normalizePreferences(array $preferences): array
    {
        $normalized = $this->getDefaultPreferences();

        foreach (self::CATEGORIES as $category) {
            if ($category === 'necessary') {
                continue;
            }

            if (array_key_exists($category, $preferences)) {
                $normalized[$category] = filter_var($preferences[$category], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $normalized;
    }

    private function hashIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        return hash_hmac('sha256', $ipAddress, $this->hashSecret);
    }

    private function truncateUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 255);
    }
}
