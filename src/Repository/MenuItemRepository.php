<?php

namespace App\Repository;

use App\Entity\Menu;
use App\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    /**
     * Get all root items (no parent) for a menu location
     */
    public function findRootItemsByLocation(string $location): array
    {
        return $this->createQueryBuilder('mi')
            ->join('mi.menu', 'm')
            ->andWhere('m.location = :location')
            ->andWhere('m.active = true')
            ->andWhere('mi.active = true')
            ->andWhere('mi.parent IS NULL')
            ->setParameter('location', $location)
            ->orderBy('mi.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all items for a menu
     */
    public function findByMenu(Menu $menu): array
    {
        return $this->createQueryBuilder('mi')
            ->andWhere('mi.menu = :menu')
            ->setParameter('menu', $menu)
            ->orderBy('mi.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get next position for a menu
     */
    public function getNextPosition(Menu $menu, ?MenuItem $parent = null): int
    {
        $qb = $this->createQueryBuilder('mi')
            ->select('MAX(mi.position)')
            ->andWhere('mi.menu = :menu')
            ->setParameter('menu', $menu);

        if ($parent) {
            $qb->andWhere('mi.parent = :parent')
                ->setParameter('parent', $parent);
        } else {
            $qb->andWhere('mi.parent IS NULL');
        }

        $result = $qb->getQuery()->getSingleScalarResult();

        return ($result ?? -1) + 1;
    }

    public function save(MenuItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MenuItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
