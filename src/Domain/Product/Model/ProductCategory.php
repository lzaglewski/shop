<?php

declare(strict_types=1);

namespace App\Domain\Model\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ProductCategory
{
    private int $id;
    private string $name;
    private ?string $description;
    private Collection $products;
    private ?ProductCategory $parent;
    private Collection $children;

    public function __construct(
        string $name,
        ?string $description = null,
        ?ProductCategory $parent = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->parent = $parent;
        $this->products = new ArrayCollection();
        $this->children = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): void
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setCategory($this);
        }
    }

    public function removeProduct(Product $product): void
    {
        if ($this->products->removeElement($product)) {
            if ($product->getCategory() === $this) {
                $product->setCategory(null);
            }
        }
    }

    public function getParent(): ?ProductCategory
    {
        return $this->parent;
    }

    public function setParent(?ProductCategory $parent): void
    {
        $this->parent = $parent;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(ProductCategory $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
    }

    public function removeChild(ProductCategory $child): void
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
    }
}
