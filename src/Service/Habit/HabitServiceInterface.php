<?php

namespace App\Service\Habit;

use App\Entity\Habit;
use App\Entity\User;

interface HabitServiceInterface
{
    /**
     * Create a new habit
     */
    public function createHabit(Habit $habit, User $user): Habit;

    /**
     * Update an existing habit
     */
    public function updateHabit(Habit $habit): void;

    /**
     * Delete a habit
     */
    public function deleteHabit(Habit $habit): void;

    /**
     * Toggle habit completion for a specific date
     */
    public function toggleHabitCompletion(Habit $habit, \DateTimeInterface $date): void;

    /**
     * Get habit statistics for a given period
     *
     * @return array{
     *     total_habits: int,
     *     active_habits: int,
     *     completion_rate: float,
     *     streak: int,
     *     completion_history: array<\DateTimeInterface, bool>
     * }
     */
    public function getHabitStatistics(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array;
}
