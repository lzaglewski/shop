<?php

declare(strict_types=1);

namespace App\Domain\Product\Model;

use App\Domain\Pricing\Model\ClientPrice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $sku;
    #[ORM\Column(type: 'text')]
    private string $description;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $basePrice;
    #[ORM\Column(type: 'integer')]
    private int $stock;
    #[ORM\Column(type: 'boolean')]
    private bool $isActive;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageFilename;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $images = null;
    #[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?ProductCategory $category;
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ClientPrice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $clientPrices;

    public function __construct(
        string           $name,
        string           $sku,
        string           $description,
        float            $basePrice,
        int              $stock,
        ?ProductCategory $category = null,
        ?string          $imageFilename = null
    )
    {
        $this->name = $name;
        $this->sku = $sku;
        $this->description = $description;
        $this->basePrice = (string)$basePrice;
        $this->stock = $stock;
        $this->category = $category;
        $this->imageFilename = $imageFilename;
        $this->images = [];
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
        return (float)$this->basePrice;
    }

    public function setBasePrice(float $basePrice): void
    {
        $this->basePrice = (string)$basePrice;
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

    public function getImages(): array
    {
        return $this->images ?? [];
    }

    public function setImages(?array $images): void
    {
        $this->images = $images;
    }

    public function addImage(string $filename): void
    {
        if ($this->images === null) {
            $this->images = [];
        }

        if (!in_array($filename, $this->images)) {
            $this->images[] = $filename;
        }
    }

    public function removeImage(string $filename): void
    {
        if ($this->images !== null) {
            $this->images = array_filter($this->images, fn($image) => $image !== $filename);
            $this->images = array_values($this->images); // Reindex array
        }
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
