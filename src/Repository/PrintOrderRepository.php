<?php

namespace App\Repository;

use App\Entity\PrintOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrintOrder>
 */
class PrintOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrintOrder::class);
    }

    public function findByCustomer($customer, array $orderBy = null): array
    {
        return $this->findBy(['customer' => $customer], $orderBy ?? ['createdAt' => 'DESC']);
    }

    public function findPendingOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOrdersToUpdateFromCewe(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.ceweOrderId IS NOT NULL')
            ->andWhere('o.status NOT IN (:finalStatuses)')
            ->setParameter('finalStatuses', ['delivered', 'cancelled'])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}