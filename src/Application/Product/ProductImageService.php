<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Application\Image\ThumbnailService;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductImageService
{
    private SluggerInterface $slugger;
    private string $productImagesDirectory;
    private ThumbnailService $thumbnailService;

    public function __construct(SluggerInterface $slugger, string $productImagesDirectory, ThumbnailService $thumbnailService)
    {
        $this->slugger = $slugger;
        $this->productImagesDirectory = $productImagesDirectory;
        $this->thumbnailService = $thumbnailService;
    }

    public function handleImageUpload(UploadedFile $imageFile, int $productId): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

        $productDirectory = $this->getProductDirectory($productId);
        $this->ensureDirectoryExists($productDirectory);

        try {
            $imageFile->move($productDirectory, $newFilename);
            
            // Create thumbnails after successful upload
            $this->createThumbnailsForImage($newFilename, $productId);
            
            return $newFilename;
        } catch (FileException $e) {
            throw new FileException('There was an error uploading the image: ' . $e->getMessage());
        }
    }

    public function handleMultipleImageUpload(array $imageFiles, int $productId): array
    {
        $uploadedFilenames = [];
        
        foreach ($imageFiles as $imageFile) {
            try {
                $uploadedFilenames[] = $this->handleImageUpload($imageFile, $productId);
            } catch (FileException $e) {
                throw new FileException('There was an error uploading one of the images: ' . $e->getMessage());
            }
        }
        
        return $uploadedFilenames;
    }

    public function deleteImage(string $filename, int $productId): void
    {
        $productDirectory = $this->getProductDirectory($productId);
        $imagePath = $productDirectory . '/' . $filename;
        
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Delete thumbnails as well
        $this->thumbnailService->deleteThumbnails($productDirectory, $filename);
    }

    public function replaceMainImage(?string $oldFilename, int $productId): void
    {
        if ($oldFilename) {
            $this->deleteImage($oldFilename, $productId);
        }
    }

    public function getProductDirectory(int $productId): string
    {
        return $this->productImagesDirectory . '/' . $productId;
    }

    public function getImagePath(string $filename, int $productId): string
    {
        return '/uploads/products/' . $productId . '/' . $filename;
    }

    public function createThumbnailsForImage(string $filename, int $productId): void
    {
        $productDirectory = $this->getProductDirectory($productId);
        $originalPath = $productDirectory . '/' . $filename;
        
        try {
            $this->thumbnailService->createThumbnails($originalPath, $productDirectory, $filename);
        } catch (FileException $e) {
            // Log the error but don't fail the upload
            error_log("Failed to create thumbnails for {$filename}: " . $e->getMessage());
        }
    }

    public function getThumbnailPath(string $filename, string $size, int $productId): string
    {
        return $this->thumbnailService->getThumbnailPath($filename, $size, $productId);
    }

    public function thumbnailExists(string $filename, string $size, int $productId): bool
    {
        $productDirectory = $this->getProductDirectory($productId);
        return $this->thumbnailService->thumbnailExists($productDirectory, $filename, $size);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new FileException('Could not create directory: ' . $directory);
            }
        }
    }
}