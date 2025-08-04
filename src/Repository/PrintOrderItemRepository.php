<?php

namespace App\Repository;

use App\Entity\PrintOrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrintOrderItem>
 */
class PrintOrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrintOrderItem::class);
    }

    public function findByPrintOrder($printOrder): array
    {
        return $this->findBy(['printOrder' => $printOrder]);
    }

    public function getPopularFormats(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->select('i.printFormat, COUNT(i.id) as orderCount')
            ->groupBy('i.printFormat')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalPrice) as totalRevenue')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}