<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Gallery\Model\GalleryImage;
use App\Domain\Gallery\Repository\GalleryImageRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineGalleryImageRepository extends ServiceEntityRepository implements GalleryImageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalleryImage::class);
    }

    public function save(GalleryImage $galleryImage): void
    {
        $this->getEntityManager()->persist($galleryImage);
        $this->getEntityManager()->flush();
    }

    public function delete(GalleryImage $galleryImage): void
    {
        $this->getEntityManager()->remove($galleryImage);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?GalleryImage
    {
        return $this->find($id);
    }

    public function findByFilename(string $filename): ?GalleryImage
    {
        return $this->findOneBy(['filename' => $filename]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLastPosition(): int
    {
        $result = $this->createQueryBuilder('g')
            ->select('MAX(g.position) as max_position')
            ->getQuery()
            ->getOneOrNullResult();

        return $result && $result['max_position'] !== null ? (int)$result['max_position'] : 0;
    }

    public function deleteByFilename(string $filename): void
    {
        $galleryImage = $this->findByFilename($filename);
        if ($galleryImage) {
            $this->delete($galleryImage);
        }
    }

    public function updatePositions(array $positionMap): void
    {
        foreach ($positionMap as $id => $position) {
            $galleryImage = $this->find($id);
            if ($galleryImage) {
                $galleryImage->setPosition((int)$position);
                $this->getEntityManager()->persist($galleryImage);
            }
        }
        $this->getEntityManager()->flush();
    }
}
