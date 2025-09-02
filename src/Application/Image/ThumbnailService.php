<?php

declare(strict_types=1);

namespace App\Application\Image;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ThumbnailService
{
    private ImageManager $imageManager;

    public const SIZES = [
        'thumb' => [80, 80],      // Koszyk, małe podglądy
        'small' => [300, 300],    // Lista produktów
        'medium' => [500, 500],   // Strona produktu
        'large' => [1000, 1000],    // Galeria
    ];

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function createThumbnails(string $originalPath, string $targetDirectory, string $filename): array
    {
        $createdThumbnails = [];

        if (!file_exists($originalPath)) {
            throw new FileException("Original image file not found: {$originalPath}");
        }

        // Ensure thumbnails directory exists
        $thumbnailsDir = $targetDirectory . '/thumbnails';
        if (!is_dir($thumbnailsDir)) {
            if (!mkdir($thumbnailsDir, 0755, true) && !is_dir($thumbnailsDir)) {
                throw new FileException("Could not create thumbnails directory: {$thumbnailsDir}");
            }
        }

        $fileInfo = pathinfo($filename);
        $baseName = $fileInfo['filename'];
        $extension = $fileInfo['extension'] ?? 'jpg';

        try {
            $originalImage = $this->imageManager->read($originalPath);

            foreach (self::SIZES as $sizeName => [$width, $height]) {
                $thumbnailFilename = "{$baseName}_{$sizeName}.{$extension}";
                $thumbnailPath = $thumbnailsDir . '/' . $thumbnailFilename;

                // Create thumbnail with fit and center
                $thumbnail = clone $originalImage;
                $thumbnail->cover($width, $height)
                    ->save($thumbnailPath, quality: 85);

                $createdThumbnails[$sizeName] = $thumbnailFilename;
            }

            return $createdThumbnails;

        } catch (\Exception $e) {
            throw new FileException("Error creating thumbnails: " . $e->getMessage());
        }
    }

    public function getThumbnailPath(string $filename, string $size, int $productId): string
    {
        if (!isset(self::SIZES[$size])) {
            throw new \InvalidArgumentException("Invalid thumbnail size: {$size}");
        }

        $fileInfo = pathinfo($filename);
        $baseName = $fileInfo['filename'];
        $extension = $fileInfo['extension'] ?? 'jpg';

        $thumbnailFilename = "{$baseName}_{$size}.{$extension}";

        return "/uploads/products/{$productId}/thumbnails/{$thumbnailFilename}";
    }

    public function deleteThumbnails(string $targetDirectory, string $filename): void
    {
        $thumbnailsDir = $targetDirectory . '/thumbnails';

        if (!is_dir($thumbnailsDir)) {
            return;
        }

        $fileInfo = pathinfo($filename);
        $baseName = $fileInfo['filename'];
        $extension = $fileInfo['extension'] ?? 'jpg';

        foreach (array_keys(self::SIZES) as $sizeName) {
            $thumbnailFilename = "{$baseName}_{$sizeName}.{$extension}";
            $thumbnailPath = $thumbnailsDir . '/' . $thumbnailFilename;

            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }
    }

    public function thumbnailExists(string $targetDirectory, string $filename, string $size): bool
    {
        if (!isset(self::SIZES[$size])) {
            return false;
        }

        $fileInfo = pathinfo($filename);
        $baseName = $fileInfo['filename'];
        $extension = $fileInfo['extension'] ?? 'jpg';

        $thumbnailFilename = "{$baseName}_{$size}.{$extension}";
        $thumbnailPath = $targetDirectory . "/thumbnails/{$thumbnailFilename}";

        return file_exists($thumbnailPath);
    }
}
