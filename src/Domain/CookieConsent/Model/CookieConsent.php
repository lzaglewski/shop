<?php

declare(strict_types=1);

namespace App\Domain\CookieConsent\Model;

use App\Domain\User\Model\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_cookie_consents')]
#[ORM\UniqueConstraint(name: 'uniq_cookie_consent_user', columns: ['user_id'])]
class CookieConsent
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $analytics;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $marketing;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $personalization;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, bool $analytics = false, bool $marketing = false, bool $personalization = false)
    {
        $this->user = $user;
        $this->analytics = $analytics;
        $this->marketing = $marketing;
        $this->personalization = $personalization;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAnalytics(): bool
    {
        return $this->analytics;
    }

    public function getMarketing(): bool
    {
        return $this->marketing;
    }

    public function getPersonalization(): bool
    {
        return $this->personalization;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateFromPreferences(array $preferences): void
    {
        $this->analytics = (bool)($preferences['analytics'] ?? false);
        $this->marketing = (bool)($preferences['marketing'] ?? false);
        $this->personalization = (bool)($preferences['personalization'] ?? false);
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPreferences(): array
    {
        return [
            'necessary' => true,
            'analytics' => $this->analytics,
            'marketing' => $this->marketing,
            'personalization' => $this->personalization,
        ];
    }
}
