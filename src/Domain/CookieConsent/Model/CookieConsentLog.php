<?php

declare(strict_types=1);

namespace App\Domain\CookieConsent\Model;

use App\Domain\User\Model\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cookie_consent_logs')]
#[ORM\Index(name: 'idx_consent_log_user', columns: ['user_id'])]
class CookieConsentLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(type: 'json')]
    private array $preferences;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $ipHash;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column(type: 'string', length: 32)]
    private string $action;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        array $preferences,
        string $action,
        ?User $user = null,
        ?string $ipHash = null,
        ?string $userAgent = null
    ) {
        $this->preferences = $preferences;
        $this->action = $action;
        $this->user = $user;
        $this->ipHash = $ipHash;
        $this->userAgent = $userAgent;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getPreferences(): array
    {
        return $this->preferences;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
