<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Application\Common\SettingsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CurrencyExtension extends AbstractExtension
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currency', [$this, 'getCurrency']),
        ];
    }

    public function getCurrency(): string
    {
        return $this->settingsService->getCurrency();
    }
}