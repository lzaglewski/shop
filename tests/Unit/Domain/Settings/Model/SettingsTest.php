<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Settings\Model;

use App\Domain\Product\Model\ProductCategory;
use App\Domain\Settings\Model\Settings;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testCreateSettingsWithoutCategory(): void
    {
        $settingKey = 'homepage_title';
        $settingValue = 'Welcome to Our Shop';

        $settings = new Settings($settingKey, $settingValue);

        $this->assertEquals($settingKey, $settings->getSettingKey());
        $this->assertEquals($settingValue, $settings->getSettingValue());
        $this->assertNull($settings->getCategory());
    }

    public function testCreateSettingsWithCategory(): void
    {
        $settingKey = 'featured_category';
        $settingValue = 'electronics';
        $category = $this->createMock(ProductCategory::class);

        $settings = new Settings($settingKey, $settingValue, $category);

        $this->assertEquals($settingKey, $settings->getSettingKey());
        $this->assertEquals($settingValue, $settings->getSettingValue());
        $this->assertSame($category, $settings->getCategory());
    }

    public function testCreateSettingsWithNullValue(): void
    {
        $settingKey = 'optional_setting';

        $settings = new Settings($settingKey);

        $this->assertEquals($settingKey, $settings->getSettingKey());
        $this->assertNull($settings->getSettingValue());
        $this->assertNull($settings->getCategory());
    }

    public function testCreateSettingsWithNullValueAndCategory(): void
    {
        $settingKey = 'category_setting';
        $category = $this->createMock(ProductCategory::class);

        $settings = new Settings($settingKey, null, $category);

        $this->assertEquals($settingKey, $settings->getSettingKey());
        $this->assertNull($settings->getSettingValue());
        $this->assertSame($category, $settings->getCategory());
    }

    public function testSetSettingKey(): void
    {
        $settings = new Settings('initial_key', 'initial_value');

        $newKey = 'updated_key';
        $settings->setSettingKey($newKey);

        $this->assertEquals($newKey, $settings->getSettingKey());
    }

    public function testSetSettingValue(): void
    {
        $settings = new Settings('test_key', 'initial_value');

        $newValue = 'updated_value';
        $settings->setSettingValue($newValue);

        $this->assertEquals($newValue, $settings->getSettingValue());

        // Test setting value to null
        $settings->setSettingValue(null);
        $this->assertNull($settings->getSettingValue());
    }

    public function testSetCategory(): void
    {
        $settings = new Settings('test_key', 'test_value');

        $category = $this->createMock(ProductCategory::class);
        $settings->setCategory($category);

        $this->assertSame($category, $settings->getCategory());

        // Test setting category to null
        $settings->setCategory(null);
        $this->assertNull($settings->getCategory());
    }

    public function testUpdateAllProperties(): void
    {
        $settings = new Settings('original_key', 'original_value');

        $newKey = 'updated_key';
        $newValue = 'updated_value';
        $newCategory = $this->createMock(ProductCategory::class);

        $settings->setSettingKey($newKey);
        $settings->setSettingValue($newValue);
        $settings->setCategory($newCategory);

        $this->assertEquals($newKey, $settings->getSettingKey());
        $this->assertEquals($newValue, $settings->getSettingValue());
        $this->assertSame($newCategory, $settings->getCategory());
    }

    public function testSettingsForBooleanValues(): void
    {
        // Test ustawień dla wartości boolean (jako string)
        $settings = new Settings('maintenance_mode', 'true');

        $this->assertEquals('true', $settings->getSettingValue());

        $settings->setSettingValue('false');
        $this->assertEquals('false', $settings->getSettingValue());
    }

    public function testSettingsForNumericValues(): void
    {
        // Test ustawień dla wartości numerycznych (jako string)
        $settings = new Settings('max_products_per_page', '20');

        $this->assertEquals('20', $settings->getSettingValue());

        $settings->setSettingValue('50');
        $this->assertEquals('50', $settings->getSettingValue());
    }

    public function testSettingsForJsonValues(): void
    {
        // Test ustawień dla wartości JSON (jako string)
        $jsonValue = '{"colors":["red","blue","green"],"sizes":["S","M","L"]}';
        $settings = new Settings('product_filters', $jsonValue);

        $this->assertEquals($jsonValue, $settings->getSettingValue());

        $newJsonValue = '{"updated":true,"timestamp":"2023-01-01"}';
        $settings->setSettingValue($newJsonValue);
        $this->assertEquals($newJsonValue, $settings->getSettingValue());
    }

    public function testSettingsForLongValues(): void
    {
        // Test ustawień dla długich wartości (maksymalnie 1000 znaków według schema)
        $longValue = str_repeat('A', 1000);
        $settings = new Settings('long_description', $longValue);

        $this->assertEquals($longValue, $settings->getSettingValue());
        $this->assertEquals(1000, strlen($settings->getSettingValue()));
    }

    public function testSettingsKeyUniqueness(): void
    {
        // Test że różne obiekty Settings mogą mieć różne klucze
        $settings1 = new Settings('key_1', 'value_1');
        $settings2 = new Settings('key_2', 'value_2');

        $this->assertNotEquals($settings1->getSettingKey(), $settings2->getSettingKey());
        $this->assertEquals('key_1', $settings1->getSettingKey());
        $this->assertEquals('key_2', $settings2->getSettingKey());
    }

    public function testCategoryRelationship(): void
    {
        // Test relacji z ProductCategory
        $category1 = $this->createMock(ProductCategory::class);
        $category1->method('getId')->willReturn(1);
        $category1->method('getName')->willReturn('Electronics');

        $category2 = $this->createMock(ProductCategory::class);
        $category2->method('getId')->willReturn(2);
        $category2->method('getName')->willReturn('Clothing');

        $settings = new Settings('featured_category', 'electronics', $category1);

        $this->assertSame($category1, $settings->getCategory());

        // Zmień kategorię
        $settings->setCategory($category2);
        $this->assertSame($category2, $settings->getCategory());
        $this->assertNotSame($category1, $settings->getCategory());
    }

    public function testComplexScenarios(): void
    {
        // Test złożonych scenariuszy użycia Settings

        // Scenariusz 1: Ustawienia głównej strony
        $homepageSettings = new Settings('homepage_title', 'Electronics Store - Best Deals Online');
        $this->assertEquals('homepage_title', $homepageSettings->getSettingKey());
        $this->assertEquals('Electronics Store - Best Deals Online', $homepageSettings->getSettingValue());

        // Scenariusz 2: Ustawienia z kategorią
        $featuredCategory = $this->createMock(ProductCategory::class);
        $featuredCategory->method('getName')->willReturn('Featured Products');
        
        $featuredSettings = new Settings('homepage_featured', null, $featuredCategory);
        $this->assertEquals('homepage_featured', $featuredSettings->getSettingKey());
        $this->assertNull($featuredSettings->getSettingValue());
        $this->assertSame($featuredCategory, $featuredSettings->getCategory());

        // Scenariusz 3: Aktualizacja ustawień
        $configSettings = new Settings('app_config', '{"theme":"dark","language":"pl"}');
        
        // Aktualizuj wartość
        $newConfig = '{"theme":"light","language":"en","notifications":true}';
        $configSettings->setSettingValue($newConfig);
        $this->assertEquals($newConfig, $configSettings->getSettingValue());

        // Scenariusz 4: Ustawienia z długimi wartościami
        $descriptionSettings = new Settings(
            'store_description',
            'Nasza firma działa od 2010 roku i specjalizuje się w sprzedaży wysokiej jakości elektroniki. ' .
            'Oferujemy szeroki wybór produktów od najlepszych światowych marek. Zapewniamy szybką dostawę, ' .
            'profesjonalny serwis i konkurencyjne ceny. Nasz doświadczony zespół służy pomocą w doborze ' .
            'odpowiednich produktów zgodnie z Państwa potrzebami i budżetem.'
        );

        $this->assertEquals('store_description', $descriptionSettings->getSettingKey());
        $this->assertNotEmpty($descriptionSettings->getSettingValue());
        $this->assertGreaterThan(100, strlen($descriptionSettings->getSettingValue()));
    }

    public function testEdgeCases(): void
    {
        // Test przypadków brzegowych

        // Pusty klucz
        $emptyKeySettings = new Settings('', 'some_value');
        $this->assertEquals('', $emptyKeySettings->getSettingKey());

        // Bardzo długi klucz
        $longKey = str_repeat('key_', 60); // 240 znaków (poniżej limitu 255)
        $longKeySettings = new Settings($longKey, 'value');
        $this->assertEquals($longKey, $longKeySettings->getSettingKey());

        // Specjalne znaki w wartości
        $specialCharSettings = new Settings('special_chars', 'Value with "quotes" and \'apostrophes\' and <tags>');
        $this->assertStringContainsString('"quotes"', $specialCharSettings->getSettingValue());
        $this->assertStringContainsString('\'apostrophes\'', $specialCharSettings->getSettingValue());

        // Wartość z polskimi znakami
        $polishSettings = new Settings('polish_text', 'Żółć gęślą jaźń');
        $this->assertEquals('Żółć gęślą jaźń', $polishSettings->getSettingValue());
    }
}