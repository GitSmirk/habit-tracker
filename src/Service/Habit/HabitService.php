<?php

namespace App\Service\Habit;

use App\Entity\Habit;
use App\Entity\HabitCompletion;
use App\Entity\User;
use App\Repository\HabitCompletionRepository;
use App\Repository\HabitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class HabitService implements HabitServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HabitRepository $habitRepository,
        private HabitCompletionRepository $habitCompletionRepository,
        private LoggerInterface $logger
    ) {}

    public function createHabit(Habit $habit, User $user): Habit
    {
        try {
            $habit->setUser($user);
            $this->entityManager->persist($habit);
            $this->entityManager->flush();

            $this->logger->info('Habit created', [
                'habit_id' => $habit->getId(),
                'user_id' => $user->getId(),
            ]);

            return $habit;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create habit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to create habit', 0, $e);
        }
    }

    public function updateHabit(Habit $habit): void
    {
        try {
            $this->entityManager->persist($habit);
            $this->entityManager->flush();

            $this->logger->info('Habit updated', [
                'habit_id' => $habit->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update habit', [
                'habit_id' => $habit->getId(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to update habit', 0, $e);
        }
    }

    public function deleteHabit(Habit $habit): void
    {
        try {
            $habitId = $habit->getId();
            $this->entityManager->remove($habit);
            $this->entityManager->flush();

            $this->logger->info('Habit deleted', [
                'habit_id' => $habitId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete habit', [
                'habit_id' => $habit->getId(),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to delete habit', 0, $e);
        }
    }

    public function toggleHabitCompletion(Habit $habit, \DateTimeInterface $date): void
    {
        try {
            $completionDate = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0);

            // Check if there's already a completion for this date
            $existingCompletion = $this->habitCompletionRepository->findOneBy([
                'habit' => $habit,
                'completionDate' => $completionDate,
            ]);

            if ($existingCompletion) {
                // If completion exists, remove it (toggle off)
                $this->entityManager->remove($existingCompletion);
                $action = 'removed';
            } else {
                // If no completion exists, create a new one (toggle on)
                $completion = new HabitCompletion();
                $completion->setHabit($habit);
                $completion->setCompletionDate($completionDate);
                $completion->setCompletedAt(new \DateTimeImmutable());

                $this->entityManager->persist($completion);
                $action = 'added';
            }

            $this->entityManager->flush();

            $this->logger->info("Habit completion {$action}", [
                'habit_id' => $habit->getId(),
                'date' => $completionDate->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to toggle habit completion', [
                'habit_id' => $habit->getId(),
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to toggle habit completion', 0, $e);
        }
    }

    public function getHabitStatistics(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        try {
            $habits = $this->habitRepository->findBy(['user' => $user]);
            $totalHabits = \count($habits);
            $activeHabits = array_reduce(
                $habits,
                fn(int $count, Habit $habit) => $count + ($habit->isActive() ? 1 : 0),
                0
            );

            $completionHistory = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                $completionHistory[$currentDate->format('Y-m-d')] = [];
                // Important: DateTimeImmutable::modify returns a NEW instance; assign it.
                $currentDate = $currentDate->modify('+1 day');
            }

            // Get all completions for the user's habits within the date range
            $completions = $this->habitCompletionRepository->createQueryBuilder('hc')
                ->join('hc.habit', 'h')
                ->where('h.user = :user')
                ->andWhere('hc.completionDate BETWEEN :startDate AND :endDate')
                ->setParameter('user', $user)
                ->setParameter('startDate', $startDate->format('Y-m-d'))
                ->setParameter('endDate', $endDate->format('Y-m-d'))
                ->getQuery()
                ->getResult();

            // Organize completions by date
            foreach ($completions as $completion) {
                $dateKey = $completion->getCompletionDate()->format('Y-m-d');
                if (isset($completionHistory[$dateKey])) {
                    $completionHistory[$dateKey][] = $completion->getHabit()->getId();
                }
            }

            // Calculate completion rate (percentage of active habits completed on average)
            $totalPossibleCompletions = $activeHabits * (clone $startDate)->diff($endDate)->days;
            $actualCompletions = array_sum(array_map('count', $completionHistory));
            $completionRate = $totalPossibleCompletions > 0
                ? ($actualCompletions / $totalPossibleCompletions) * 100
                : 0;

            // Calculate current streak (consecutive days with at least one completion)
            $streak = 0;
            $today = new \DateTimeImmutable();
            $checkDate = min($endDate, $today);

            while ($checkDate >= $startDate) {
                $dateKey = $checkDate->format('Y-m-d');
                if (!empty($completionHistory[$dateKey] ?? [])) {
                    $streak++;
                } else {
                    break;
                }
                $checkDate = $checkDate->modify('-1 day');
            }

            return [
                'total_habits' => $totalHabits,
                'active_habits' => $activeHabits,
                'completion_rate' => round($completionRate, 2),
                'streak' => $streak,
                'completion_history' => $completionHistory,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get habit statistics', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            // Return default values in case of error
            return [
                'total_habits' => 0,
                'active_habits' => 0,
                'completion_rate' => 0.0,
                'streak' => 0,
                'completion_history' => [],
            ];
        }
    }
}
