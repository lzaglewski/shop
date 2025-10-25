<?php

declare(strict_types=1);

namespace App\Application\Gallery;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class GalleryImageUploadService
{
    private SluggerInterface $slugger;
    private string $galleryImagesDirectory;

    public function __construct(SluggerInterface $slugger, string $galleryImagesDirectory)
    {
        $this->slugger = $slugger;
        $this->galleryImagesDirectory = $galleryImagesDirectory;
    }

    /**
     * Upload single image to gallery
     */
    public function uploadImage(UploadedFile $imageFile): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

        $this->ensureDirectoryExists();

        try {
            $imageFile->move($this->galleryImagesDirectory, $newFilename);
            return $newFilename;
        } catch (FileException $e) {
            throw new FileException('Error uploading gallery image: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple images to gallery
     */
    public function uploadMultipleImages(array $imageFiles): array
    {
        $uploadedFilenames = [];

        foreach ($imageFiles as $imageFile) {
            if (!$imageFile instanceof UploadedFile) {
                continue;
            }
            try {
                $uploadedFilenames[] = $this->uploadImage($imageFile);
            } catch (FileException $e) {
                throw new FileException('Error uploading one of the gallery images: ' . $e->getMessage());
            }
        }

        return $uploadedFilenames;
    }

    /**
     * Delete image from gallery
     */
    public function deleteImage(string $filename): void
    {
        $imagePath = $this->galleryImagesDirectory . '/' . $filename;

        if (file_exists($imagePath)) {
            try {
                unlink($imagePath);
            } catch (\Exception $e) {
                throw new FileException('Error deleting gallery image: ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if image exists
     */
    public function imageExists(string $filename): bool
    {
        return file_exists($this->galleryImagesDirectory . '/' . $filename);
    }

    /**
     * Get gallery image path for rendering
     */
    public function getImagePath(string $filename): string
    {
        return '/uploads/gallery/' . $filename;
    }

    /**
     * Get full file system path
     */
    public function getFullPath(string $filename): string
    {
        return $this->galleryImagesDirectory . '/' . $filename;
    }

    /**
     * Get gallery directory path
     */
    public function getGalleryDirectory(): string
    {
        return $this->galleryImagesDirectory;
    }

    /**
     * Ensure gallery directory exists
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->galleryImagesDirectory)) {
            if (!mkdir($this->galleryImagesDirectory, 0755, true) && !is_dir($this->galleryImagesDirectory)) {
                throw new FileException('Could not create gallery directory: ' . $this->galleryImagesDirectory);
            }
        }
    }
}
