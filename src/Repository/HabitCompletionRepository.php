<?php

namespace App\Repository;

use App\Entity\Habit;
use App\Entity\HabitCompletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HabitCompletion>
 *
 * @method HabitCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method HabitCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method HabitCompletion[]    findAll()
 * @method HabitCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HabitCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HabitCompletion::class);
    }

    public function save(HabitCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HabitCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find completions for a specific habit within a date range
     *
     * @param Habit $habit
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return HabitCompletion[]
     */
    public function findCompletionsInDateRange(
        Habit $habit,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('hc')
            ->andWhere('hc.habit = :habit')
            ->andWhere('hc.completionDate BETWEEN :startDate AND :endDate')
            ->setParameter('habit', $habit)
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
            ->orderBy('hc.completionDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a habit was completed on a specific date
     */
    public function isHabitCompletedOnDate(Habit $habit, \DateTimeInterface $date): bool
    {
        $result = $this->createQueryBuilder('hc')
            ->select('COUNT(hc.id)')
            ->andWhere('hc.habit = :habit')
            ->andWhere('hc.completionDate = :date')
            ->setParameter('habit', $habit)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$result > 0;
    }
}
