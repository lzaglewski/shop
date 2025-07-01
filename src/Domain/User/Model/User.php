<?php

declare(strict_types=1);

namespace App\Domain\User\Model;

use App\Domain\Pricing\Model\ClientPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private int $id;
    private string $email;
    private string $password;
    private string $companyName;
    private ?string $taxId;
    private UserRole $role;
    private bool $isActive;
    private Collection $clientPrices;

    public function __construct(
        string $email,
        string $password,
        string $companyName,
        ?string $taxId = null,
        UserRole $role = UserRole::CLIENT
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->companyName = $companyName;
        $this->taxId = $taxId;
        $this->role = $role;
        $this->isActive = true;
        $this->clientPrices = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getClientPrices(): Collection
    {
        return $this->clientPrices;
    }

    public function addClientPrice(ClientPrice $clientPrice): void
    {
        if (!$this->clientPrices->contains($clientPrice)) {
            $this->clientPrices->add($clientPrice);
            $clientPrice->setClient($this);
        }
    }

    public function removeClientPrice(ClientPrice $clientPrice): void
    {
        if ($this->clientPrices->removeElement($clientPrice)) {
            if ($clientPrice->getClient() === $this) {
                $clientPrice->setClient(null);
            }
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
