<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Product\Model\ProductCategory;
use App\Domain\Settings\Model\Settings;
use App\Domain\Settings\Repository\SettingsRepositoryInterface;

class SettingsService
{
    public const HOMEPAGE_CATEGORY_KEY = 'homepage_featured_category';

    private SettingsRepositoryInterface $settingsRepository;

    public function __construct(SettingsRepositoryInterface $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function getHomepageCategory(): ?ProductCategory
    {
        $setting = $this->settingsRepository->findByKey(self::HOMEPAGE_CATEGORY_KEY);
        return $setting?->getCategory();
    }

    public function setHomepageCategory(?ProductCategory $category): void
    {
        $setting = $this->settingsRepository->findByKey(self::HOMEPAGE_CATEGORY_KEY);
        
        if (!$setting) {
            $setting = new Settings(self::HOMEPAGE_CATEGORY_KEY);
        }
        
        $setting->setCategory($category);
        $this->settingsRepository->save($setting);
    }

    public function getSetting(string $key): ?Settings
    {
        return $this->settingsRepository->findByKey($key);
    }

    public function setSetting(string $key, ?string $value = null, ?ProductCategory $category = null): void
    {
        $setting = $this->settingsRepository->findByKey($key);
        
        if (!$setting) {
            $setting = new Settings($key, $value, $category);
        } else {
            $setting->setSettingValue($value);
            $setting->setCategory($category);
        }
        
        $this->settingsRepository->save($setting);
    }
}