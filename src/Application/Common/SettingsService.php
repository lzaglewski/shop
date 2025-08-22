<?php

declare(strict_types=1);

namespace App\Application\Common;

use App\Domain\Product\Model\ProductCategory;
use App\Domain\Settings\Model\Settings;
use App\Domain\Settings\Repository\SettingsRepositoryInterface;

class SettingsService
{
    public const HOMEPAGE_CATEGORY_KEY = 'homepage_featured_category';
    public const SMTP_HOST_KEY = 'smtp_host';
    public const SMTP_PORT_KEY = 'smtp_port';
    public const SMTP_USERNAME_KEY = 'smtp_username';
    public const SMTP_PASSWORD_KEY = 'smtp_password';
    public const SMTP_ENCRYPTION_KEY = 'smtp_encryption';
    public const MAIL_FROM_EMAIL_KEY = 'mail_from_email';
    public const MAIL_FROM_NAME_KEY = 'mail_from_name';
    public const MAIL_ADMIN_EMAILS_KEY = 'mail_admin_emails';
    public const MAIL_NOTIFICATIONS_ENABLED_KEY = 'mail_notifications_enabled';
    public const CURRENCY_KEY = 'currency';

    private SettingsRepositoryInterface $settingsRepository;

    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function getHomepageCategory(): ?ProductCategory
    {
        $setting = $this->settingsRepository->findByKey(self::HOMEPAGE_CATEGORY_KEY);
        return $setting?->getCategory();
    }

    public function setHomepageCategory(?ProductCategory $category): void
    {
        $setting = $this->settingsRepository->findByKey(self::HOMEPAGE_CATEGORY_KEY);

        if (!$setting) {
            $setting = new Settings(self::HOMEPAGE_CATEGORY_KEY);
        }

        $setting->setCategory($category);
        $this->settingsRepository->save($setting);
    }

    public function getSetting(string $key): ?Settings
    {
        return $this->settingsRepository->findByKey($key);
    }

    public function setSetting(string $key, ?string $value = null, ?ProductCategory $category = null): void
    {
        $setting = $this->settingsRepository->findByKey($key);

        if (!$setting) {
            $setting = new Settings($key, $value, $category);
        } else {
            $setting->setSettingValue($value);
            $setting->setCategory($category);
        }

        $this->settingsRepository->save($setting);
    }

    // SMTP Configuration methods
    public function getSmtpHost(): ?string
    {
        return $this->getSetting(self::SMTP_HOST_KEY)?->getSettingValue();
    }

    public function setSmtpHost(string $host): void
    {
        $this->setSetting(self::SMTP_HOST_KEY, $host);
    }

    public function getSmtpPort(): ?int
    {
        $value = $this->getSetting(self::SMTP_PORT_KEY)?->getSettingValue();
        return $value ? (int) $value : null;
    }

    public function setSmtpPort(int $port): void
    {
        $this->setSetting(self::SMTP_PORT_KEY, (string) $port);
    }

    public function getSmtpUsername(): ?string
    {
        return $this->getSetting(self::SMTP_USERNAME_KEY)?->getSettingValue();
    }

    public function setSmtpUsername(string $username): void
    {
        $this->setSetting(self::SMTP_USERNAME_KEY, $username);
    }

    public function getSmtpPassword(): ?string
    {
        return $this->getSetting(self::SMTP_PASSWORD_KEY)?->getSettingValue();
    }

    public function setSmtpPassword(string $password): void
    {
        $this->setSetting(self::SMTP_PASSWORD_KEY, $password);
    }

    public function getSmtpEncryption(): ?string
    {
        return $this->getSetting(self::SMTP_ENCRYPTION_KEY)?->getSettingValue();
    }

    public function setSmtpEncryption(string $encryption): void
    {
        $this->setSetting(self::SMTP_ENCRYPTION_KEY, $encryption);
    }

    public function getMailFromEmail(): ?string
    {
        return $this->getSetting(self::MAIL_FROM_EMAIL_KEY)?->getSettingValue();
    }

    public function setMailFromEmail(string $email): void
    {
        $this->setSetting(self::MAIL_FROM_EMAIL_KEY, $email);
    }

    public function getMailFromName(): ?string
    {
        return $this->getSetting(self::MAIL_FROM_NAME_KEY)?->getSettingValue();
    }

    public function setMailFromName(string $name): void
    {
        $this->setSetting(self::MAIL_FROM_NAME_KEY, $name);
    }

    public function getMailAdminEmails(): array
    {
        $value = $this->getSetting(self::MAIL_ADMIN_EMAILS_KEY)?->getSettingValue();
        return $value ? explode(',', $value) : [];
    }

    public function setMailAdminEmails(array $emails): void
    {
        $this->setSetting(self::MAIL_ADMIN_EMAILS_KEY, implode(',', $emails));
    }

    public function isMailNotificationsEnabled(): bool
    {
        $value = $this->getSetting(self::MAIL_NOTIFICATIONS_ENABLED_KEY)?->getSettingValue();
        return $value === 'true' || $value === '1';
    }

    public function setMailNotificationsEnabled(bool $enabled): void
    {
        $this->setSetting(self::MAIL_NOTIFICATIONS_ENABLED_KEY, $enabled ? 'true' : 'false');
    }

    public function getSmtpDsn(): ?string
    {
        $host = $this->getSmtpHost();
        $port = $this->getSmtpPort();
        $username = $this->getSmtpUsername();
        $password = $this->getSmtpPassword();
        $encryption = $this->getSmtpEncryption();

        if (!$host || !$port || !$username || !$password) {
            return null;
        }

        // Build DSN with proper URL encoding for special characters
        $dsn = sprintf('smtp://%s:%s@%s:%d', urlencode($username), urlencode($password), $host, $port);
        
        if ($encryption) {
            $dsn .= '?encryption=' . $encryption;
        }

        return $dsn;
    }

    public function getCurrency(): string
    {
        return $this->getSetting(self::CURRENCY_KEY)?->getSettingValue() ?? '€';
    }

    public function setCurrency(string $currency): void
    {
        $this->setSetting(self::CURRENCY_KEY, $currency);
    }
}
