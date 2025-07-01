<?php

declare(strict_types=1);

namespace App\Domain\Model\Pricing;

use App\Domain\Model\Product\Product;
use App\Domain\Model\User\User;

class ClientPrice
{
    private int $id;
    private User $client;
    private Product $product;
    private float $price;
    private bool $isActive;

    public function __construct(
        User $client,
        Product $product,
        float $price
    ) {
        $this->client = $client;
        $this->product = $product;
        $this->price = $price;
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
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
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
