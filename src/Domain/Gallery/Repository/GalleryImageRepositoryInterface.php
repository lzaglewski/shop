<?php

declare(strict_types=1);

namespace App\Domain\Gallery\Repository;

use App\Domain\Gallery\Model\GalleryImage;

interface GalleryImageRepositoryInterface
{
    public function save(GalleryImage $galleryImage): void;

    public function delete(GalleryImage $galleryImage): void;

    public function findById(int $id): ?GalleryImage;

    public function findByFilename(string $filename): ?GalleryImage;

    public function findAll(): array;

    public function findAllOrdered(): array;

    public function findLastPosition(): int;

    public function deleteByFilename(string $filename): void;

    public function updatePositions(array $positionMap): void;
}
