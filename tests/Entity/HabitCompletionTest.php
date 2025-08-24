<?php

namespace App\Tests\Entity;

use App\Entity\Habit;
use App\Entity\HabitCompletion;
use PHPUnit\Framework\TestCase;

class HabitCompletionTest extends TestCase
{
    public function testHabitCompletionCreation()
    {
        $habit = new Habit();
        $habit->setName('Read a book');
        
        $completionDate = new \DateTimeImmutable('2023-04-01');
        $completedAt = new \DateTimeImmutable('2023-04-01 08:30:00');
        
        $habitCompletion = new HabitCompletion();
        $habitCompletion->setHabit($habit);
        $habitCompletion->setCompletionDate($completionDate);
        $habitCompletion->setNotes('Read chapter 5');
        $habitCompletion->setCompletedAt($completedAt);
        
        $this->assertSame($habit, $habitCompletion->getHabit());
        $this->assertEquals('2023-04-01', $habitCompletion->getCompletionDate()->format('Y-m-d'));
        $this->assertSame('Read chapter 5', $habitCompletion->getNotes());
        $this->assertSame($completedAt, $habitCompletion->getCompletedAt());
    }
}
