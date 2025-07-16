<?php

declare(strict_types=1);

namespace App\Domain\Settings\Repository;

use App\Domain\Settings\Model\Settings;

interface SettingsRepositoryInterface
{
    public function findByKey(string $key): ?Settings;
    public function save(Settings $settings): void;
    public function findAll(): array;
}