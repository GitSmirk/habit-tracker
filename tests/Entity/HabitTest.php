<?php

namespace App\Tests\Entity;

use App\Entity\Habit;
use PHPUnit\Framework\TestCase;

class HabitTest extends TestCase
{
    public function testHabitCreation()
    {
        $habit = new Habit();
        $habit->setName('Morning Run');
        $habit->setDescription('30-minute run every morning');
        $habit->setTargetFrequency(7); // times per week
        $habit->setIsActive(true);
        
        $this->assertSame('Morning Run', $habit->getName());
        $this->assertSame('30-minute run every morning', $habit->getDescription());
        $this->assertSame(7, $habit->getTargetFrequency());
        $this->assertTrue($habit->isActive());
        $this->assertInstanceOf(\DateTimeImmutable::class, $habit->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $habit->getUpdatedAt());
    }

    public function testHabitFrequencyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $habit = new Habit();
        $habit->setTargetFrequency(0); // Should throw an exception
    }
}
