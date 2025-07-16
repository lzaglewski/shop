<?php

declare(strict_types=1);

namespace App\Domain\Settings\Model;

use App\Domain\Product\Model\ProductCategory;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'settings')]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: 'string', length: 1000, nullable: true)]
    private ?string $settingValue;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true)]
    private ?ProductCategory $category;

    public function __construct(string $settingKey, ?string $settingValue = null, ?ProductCategory $category = null)
    {
        $this->settingKey = $settingKey;
        $this->settingValue = $settingValue;
        $this->category = $category;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): void
    {
        $this->settingKey = $settingKey;
    }

    public function getSettingValue(): ?string
    {
        return $this->settingValue;
    }

    public function setSettingValue(?string $settingValue): void
    {
        $this->settingValue = $settingValue;
    }

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): void
    {
        $this->category = $category;
    }
}