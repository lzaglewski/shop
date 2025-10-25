<?php

declare(strict_types=1);

namespace App\Application\Gallery;

use App\Domain\Gallery\Model\GalleryImage;
use App\Domain\Gallery\Repository\GalleryImageRepositoryInterface;

class GalleryService
{
    private GalleryImageRepositoryInterface $galleryRepository;

    public function __construct(GalleryImageRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Get all gallery images ordered by position
     */
    public function getAllOrdered(): array
    {
        return $this->galleryRepository->findAllOrdered();
    }

    /**
     * Get all gallery images
     */
    public function getAll(): array
    {
        return $this->galleryRepository->findAll();
    }

    /**
     * Find gallery image by ID
     */
    public function findById(int $id): ?GalleryImage
    {
        return $this->galleryRepository->findById($id);
    }

    /**
     * Add new image to gallery
     */
    public function addImage(string $filename): GalleryImage
    {
        $lastPosition = $this->galleryRepository->findLastPosition();
        $galleryImage = new GalleryImage($filename, $lastPosition + 1);
        $this->galleryRepository->save($galleryImage);

        return $galleryImage;
    }

    /**
     * Remove image from gallery
     */
    public function removeImage(int $id): void
    {
        $galleryImage = $this->galleryRepository->findById($id);
        if ($galleryImage) {
            $this->galleryRepository->delete($galleryImage);
        }
    }

    /**
     * Remove image by filename
     */
    public function removeImageByFilename(string $filename): void
    {
        $this->galleryRepository->deleteByFilename($filename);
    }

    /**
     * Update image position
     */
    public function updatePosition(int $id, int $position): void
    {
        $galleryImage = $this->galleryRepository->findById($id);
        if ($galleryImage) {
            $galleryImage->setPosition($position);
            $this->galleryRepository->save($galleryImage);
        }
    }

    /**
     * Reorder multiple images at once
     * $positions should be array of ['id' => position, ...]
     */
    public function reorderImages(array $positions): void
    {
        $positionMap = [];
        foreach ($positions as $id => $position) {
            $galleryImage = $this->galleryRepository->findById((int)$id);
            if ($galleryImage) {
                $positionMap[$id] = $position;
                $galleryImage->setPosition((int)$position);
            }
        }
        $this->galleryRepository->updatePositions($positionMap);
    }

    /**
     * Get count of gallery images
     */
    public function count(): int
    {
        return count($this->galleryRepository->findAll());
    }

    /**
     * Clear all images
     */
    public function clearAll(): void
    {
        foreach ($this->galleryRepository->findAll() as $image) {
            $this->galleryRepository->delete($image);
        }
    }
}
