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

    public function handleImageUpload(UploadedFile $imageFile): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

        try {
            $imageFile->move($this->productImagesDirectory, $newFilename);
            return $newFilename;
        } catch (FileException $e) {
            throw new FileException('There was an error uploading the image: ' . $e->getMessage());
        }
    }

    public function handleMultipleImageUpload(array $imageFiles): array
    {
        $uploadedFilenames = [];
        
        foreach ($imageFiles as $imageFile) {
            try {
                $uploadedFilenames[] = $this->handleImageUpload($imageFile);
            } catch (FileException $e) {
                throw new FileException('There was an error uploading one of the images: ' . $e->getMessage());
            }
        }
        
        return $uploadedFilenames;
    }

    public function deleteImage(string $filename): void
    {
        $imagePath = $this->productImagesDirectory . '/' . $filename;
        
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    public function replaceMainImage(?string $oldFilename, string $newFilename): void
    {
        if ($oldFilename) {
            $this->deleteImage($oldFilename);
        }
    }
}