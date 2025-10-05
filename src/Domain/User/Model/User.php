<?php

declare(strict_types=1);

namespace App\Domain\User\Model;

use App\Domain\Pricing\Model\ClientPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;
    #[ORM\Column(type: 'string', length: 180, unique: true, nullable: true)]
    private ?string $email = null;
    #[ORM\Column(type: 'string', length: 50, unique: true, nullable: true)]
    private ?string $login = null;
    #[ORM\Column(type: 'string')]
    private string $password;
    #[ORM\Column(type: 'string', length: 255)]
    private string $companyName;
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $taxId;
    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    private UserRole $role;
    #[ORM\Column(type: 'boolean')]
    private bool $isActive;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $contactNumber;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $deliveryStreet;
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $deliveryPostalCode;
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deliveryCity;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $billingCompanyName;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $billingStreet;
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $billingPostalCode;
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $billingCity;
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $billingTaxId;
    #[ORM\OneToMany(mappedBy: 'client', targetEntity: ClientPrice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $clientPrices;

    public function __construct(
        ?string $email,
        string $password,
        string $companyName,
        ?string $taxId = null,
        UserRole $role = UserRole::CLIENT,
        ?string $login = null
    ) {
        // Ensure at least email or login is provided
        if (empty($email) && empty($login)) {
            throw new \InvalidArgumentException('Either email or login must be provided');
        }

        $this->email = $email;
        $this->password = $password;
        $this->companyName = $companyName;
        $this->taxId = $taxId;
        $this->role = $role;
        $this->login = $login ? strtolower(trim($login)) : null;
        $this->isActive = true;
        $this->clientPrices = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
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
        if (!empty($this->login)) {
            return $this->login;
        }
        if (!empty($this->email)) {
            return $this->email;
        }
        throw new \LogicException('User must have either login or email');
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): void
    {
        $this->login = $login ? strtolower(trim($login)) : null;
    }

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(?string $contactNumber): void
    {
        $this->contactNumber = $contactNumber;
    }

    public function getDeliveryStreet(): ?string
    {
        return $this->deliveryStreet;
    }

    public function setDeliveryStreet(?string $deliveryStreet): void
    {
        $this->deliveryStreet = $deliveryStreet;
    }

    public function getDeliveryPostalCode(): ?string
    {
        return $this->deliveryPostalCode;
    }

    public function setDeliveryPostalCode(?string $deliveryPostalCode): void
    {
        $this->deliveryPostalCode = $deliveryPostalCode;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(?string $deliveryCity): void
    {
        $this->deliveryCity = $deliveryCity;
    }

    public function getBillingCompanyName(): ?string
    {
        return $this->billingCompanyName;
    }

    public function setBillingCompanyName(?string $billingCompanyName): void
    {
        $this->billingCompanyName = $billingCompanyName;
    }

    public function getBillingStreet(): ?string
    {
        return $this->billingStreet;
    }

    public function setBillingStreet(?string $billingStreet): void
    {
        $this->billingStreet = $billingStreet;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(?string $billingPostalCode): void
    {
        $this->billingPostalCode = $billingPostalCode;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): void
    {
        $this->billingCity = $billingCity;
    }

    public function getBillingTaxId(): ?string
    {
        return $this->billingTaxId;
    }

    public function setBillingTaxId(?string $billingTaxId): void
    {
        $this->billingTaxId = $billingTaxId;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function validateIdentifier(): void
    {
        if (empty($this->email) && empty($this->login)) {
            throw new \InvalidArgumentException('Either email or login must be provided');
        }
    }
}
