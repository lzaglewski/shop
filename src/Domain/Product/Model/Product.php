<?php

declare(strict_types=1);

namespace App\Domain\Model\Product;

use App\Domain\Model\Pricing\ClientPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Product
{
    private int $id;
    private string $name;
    private string $sku;
    private string $description;
    private float $basePrice;
    private int $stock;
    private bool $isActive;
    private ?string $imageFilename;
    private ?ProductCategory $category;
    private Collection $clientPrices;

    public function __construct(
        string $name,
        string $sku,
        string $description,
        float $basePrice,
        int $stock,
        ?ProductCategory $category = null,
        ?string $imageFilename = null
    ) {
        $this->name = $name;
        $this->sku = $sku;
        $this->description = $description;
        $this->basePrice = $basePrice;
        $this->stock = $stock;
        $this->category = $category;
        $this->imageFilename = $imageFilename;
        $this->isActive = true;
        $this->clientPrices = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): void
    {
        $this->imageFilename = $imageFilename;
    }

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): void
    {
        $this->category = $category;
    }

    public function getClientPrices(): Collection
    {
        return $this->clientPrices;
    }

    public function addClientPrice(ClientPrice $clientPrice): void
    {
        if (!$this->clientPrices->contains($clientPrice)) {
            $this->clientPrices->add($clientPrice);
            $clientPrice->setProduct($this);
        }
    }

    public function removeClientPrice(ClientPrice $clientPrice): void
    {
        if ($this->clientPrices->removeElement($clientPrice)) {
            if ($clientPrice->getProduct() === $this) {
                $clientPrice->setProduct(null);
            }
        }
    }
}
