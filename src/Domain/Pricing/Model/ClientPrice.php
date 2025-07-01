<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Model;

use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'client_prices')]
class ClientPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'clientPrices')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false)]
    private User $client;
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'clientPrices')]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private Product $product;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;
    #[ORM\Column(type: 'boolean')]
    private bool $isActive;

    public function __construct(
        User $client,
        Product $product,
        float $price
    ) {
        $this->client = $client;
        $this->product = $product;
        $this->price = (string) $price;
        $this->isActive = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getClient(): User
    {
        return $this->client;
    }

    public function setClient(?User $client): void
    {
        $this->client = $client;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getPrice(): float
    {
        return (float) $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = (string) $price;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
}
