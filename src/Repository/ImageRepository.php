<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 *
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function findByGalleryOrderedByPosition($gallery): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.gallery = :gallery')
            ->setParameter('gallery', $gallery)
            ->orderBy('i.position', 'ASC')
            ->addOrderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentImages(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getNextPosition($gallery): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('MAX(i.position)')
            ->where('i.gallery = :gallery')
            ->setParameter('gallery', $gallery)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    public function getTotalSize(): int
    {
        return $this->createQueryBuilder('i')
            ->select('SUM(i.size)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function getImagesByMimeType(): array
    {
        return $this->createQueryBuilder('i')
            ->select('i.mimeType, COUNT(i.id) as count')
            ->groupBy('i.mimeType')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByFilename(string $filename): ?Image
    {
        return $this->findOneBy(['filename' => $filename]);
    }
}