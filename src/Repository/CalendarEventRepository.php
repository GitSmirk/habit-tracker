<?php

namespace App\Repository;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarEvent>
 *
 * @method CalendarEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method CalendarEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method CalendarEvent[]    findAll()
 * @method CalendarEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CalendarEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarEvent::class);
    }

    public function save(CalendarEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CalendarEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find events for a specific habit within a date range
     *
     * @param Habit $habit
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return CalendarEvent[]
     */
    public function findEventsInDateRange(
        Habit $habit,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('e')
            ->andWhere('e.habit = :habit')
            ->andWhere('e.startDate BETWEEN :startDate AND :endDate')
            ->setParameter('habit', $habit)
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by external ID and source
     */
    public function findByExternalId(string $externalId, string $source): ?CalendarEvent
    {
        return $this->findOneBy([
            'externalId' => $externalId,
            'externalSource' => $source,
        ]);
    }

    /**
     * Find upcoming events for a habit
     *
     * @param Habit $habit
     * @param int $limit Maximum number of events to return
     * @return CalendarEvent[]
     */
    public function findUpcomingEvents(Habit $habit, int $limit = 10): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('e')
            ->andWhere('e.habit = :habit')
            ->andWhere('e.startDate >= :now')
            ->setParameter('habit', $habit)
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
