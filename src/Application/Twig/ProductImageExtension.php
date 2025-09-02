<?php

declare(strict_types=1);

namespace App\Application\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProductImageExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('product_image_path', [$this, 'getProductImagePath']),
        ];
    }

    public function getProductImagePath(string $filename, int $productId): string
    {
        return '/uploads/products/' . $productId . '/' . $filename;
    }
}