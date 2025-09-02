<?php

declare(strict_types=1);

namespace App\Application\Product;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductImageService
{
    private SluggerInterface $slugger;
    private string $productImagesDirectory;

    public function __construct(SluggerInterface $slugger, string $productImagesDirectory)
    {
        $this->slugger = $slugger;
        $this->productImagesDirectory = $productImagesDirectory;
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
        $imagePath = $this->getProductDirectory($productId) . '/' . $filename;
        
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
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

    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new FileException('Could not create directory: ' . $directory);
            }
        }
    }
}