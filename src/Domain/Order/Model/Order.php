<?php

declare(strict_types=1);

namespace App\Domain\Order\Model;

use App\Domain\User\Model\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $customerEmail;

    #[ORM\Column(type: 'string', length: 255)]
    private string $customerCompanyName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $customerTaxId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $shippingAddress;

    #[ORM\Column(type: 'string', length: 255)]
    private string $billingAddress;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalAmount;

    #[ORM\Column(type: 'string', enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt;

    public function __construct(
        string $customerEmail,
        string $customerCompanyName,
        ?string $customerTaxId,
        string $shippingAddress,
        string $billingAddress,
        ?string $notes,
        ?User $user = null
    ) {
        $this->orderNumber = $this->generateOrderNumber();
        $this->customerEmail = $customerEmail;
        $this->customerCompanyName = $customerCompanyName;
        $this->customerTaxId = $customerTaxId;
        $this->shippingAddress = $shippingAddress;
        $this->billingAddress = $billingAddress;
        $this->notes = $notes;
        $this->user = $user;
        $this->status = OrderStatus::NEW;
        $this->totalAmount = '0.00';
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    public function getCustomerCompanyName(): string
    {
        return $this->customerCompanyName;
    }

    public function setCustomerCompanyName(string $customerCompanyName): void
    {
        $this->customerCompanyName = $customerCompanyName;
    }

    public function getCustomerTaxId(): ?string
    {
        return $this->customerTaxId;
    }

    public function setCustomerTaxId(?string $customerTaxId): void
    {
        $this->customerTaxId = $customerTaxId;
    }

    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getBillingAddress(): string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(string $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getTotalAmount(): float
    {
        return (float) $this->totalAmount;
    }

    public function recalculateTotalAmount(): void
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getSubtotal();
        }
        $this->totalAmount = (string) $total;
        $this->updateTimestamp();
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): void
    {
        $this->status = $status;
        $this->updateTimestamp();
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
            $this->recalculateTotalAmount();
        }
    }

    public function removeItem(OrderItem $item): void
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
            $this->recalculateTotalAmount();
        }
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function generateOrderNumber(): string
    {
        return date('Ymd') . '-' . substr(uniqid(), -5);
    }
}
