<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Trouve tous les événements actifs triés par date de début
     */
    public function findActiveEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements à venir
     */
    public function findUpcomingEvents(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les événements pour un mois donné
     */
    public function findEventsByMonth(int $year, int $month): array
    {
        $startOfMonth = new \DateTime("$year-$month-01 00:00:00");
        $endOfMonth = clone $startOfMonth;
        $endOfMonth->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('(e.startDate BETWEEN :start AND :end) OR (e.endDate BETWEEN :start AND :end)')
            ->setParameter('status', 'active')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements par type
     */
    public function findEventsByType(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->setParameter('status', 'active')
            ->setParameter('type', $type)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements récents pour le widget
     */
    public function findRecentEventsForWidget(int $limit = 3): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :yesterday')
            ->setParameter('status', 'active')
            ->setParameter('yesterday', new \DateTime('-1 day'))
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les événements à venir
     */
    public function countUpcomingEvents(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les événements avec places disponibles
     */
    public function findEventsWithAvailableSpots(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.startDate >= :now')
            ->andWhere('e.maxParticipants IS NOT NULL')
            ->andWhere('e.currentParticipants < e.maxParticipants')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}