<?php

declare(strict_types=1);

namespace App\Application\Twig;

use App\Application\Product\ProductImageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProductImageExtension extends AbstractExtension
{
    private ProductImageService $productImageService;

    public function __construct(ProductImageService $productImageService)
    {
        $this->productImageService = $productImageService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('product_image_path', [$this, 'getProductImagePath']),
            new TwigFunction('product_thumbnail_path', [$this, 'getProductThumbnailPath']),
            new TwigFunction('product_thumbnail_or_image', [$this, 'getThumbnailOrOriginalImage']),
        ];
    }

    public function getProductImagePath(string $filename, int $productId): string
    {
        return '/uploads/products/' . $productId . '/' . $filename;
    }

    public function getProductThumbnailPath(string $filename, string $size, int $productId): string
    {
        return $this->productImageService->getThumbnailPath($filename, $size, $productId);
    }

    public function getThumbnailOrOriginalImage(string $filename, string $size, int $productId): string
    {
        // Check if thumbnail exists, if not return original image path
        if ($this->productImageService->thumbnailExists($filename, $size, $productId)) {
            return $this->getProductThumbnailPath($filename, $size, $productId);
        }
        
        return $this->getProductImagePath($filename, $productId);
    }
}