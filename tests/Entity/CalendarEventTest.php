<?php

namespace App\Tests\Entity;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class CalendarEventTest extends TestCase
{
    public function testCalendarEventCreation()
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $habit = new Habit();
        $habit->setName('Morning Run');
        $habit->setUser($user);
        
        $startDate = new \DateTimeImmutable('2023-04-01 07:00:00');
        $endDate = new \DateTimeImmutable('2023-04-01 07:30:00');
        
        $event = new CalendarEvent();
        $event->setHabit($habit);
        $event->setTitle('Morning Run');
        $event->setDescription('30-minute run in the park');
        $event->setStartDate($startDate);
        $event->setEndDate($endDate);
        $event->setExternalId('google-event-123');
        $event->setExternalSource('google');
        
        $this->assertSame($habit, $event->getHabit());
        $this->assertSame('Morning Run', $event->getTitle());
        $this->assertSame('30-minute run in the park', $event->getDescription());
        $this->assertSame($startDate, $event->getStartDate());
        $this->assertSame($endDate, $event->getEndDate());
        $this->assertSame('google-event-123', $event->getExternalId());
        $this->assertSame('google', $event->getExternalSource());
        $this->assertFalse($event->isRecurring());
    }
    
    public function testRecurringEvent()
    {
        $habit = new Habit();
        $habit->setName('Weekly Review');
        
        $startDate = new \DateTimeImmutable('2023-04-01 09:00:00');
        $endDate = new \DateTimeImmutable('2023-04-01 10:00:00');
        $recurrenceEndDate = new \DateTimeImmutable('2023-12-31 23:59:59');
        
        $event = new CalendarEvent();
        $event->setHabit($habit);
        $event->setTitle('Weekly Review');
        $event->setStartDate($startDate);
        $event->setEndDate($endDate);
        $event->setRecurrenceRule('FREQ=WEEKLY;BYDAY=MO;INTERVAL=1');
        $event->setRecurrenceEndDate($recurrenceEndDate);
        
        $this->assertTrue($event->isRecurring());
        $this->assertSame('FREQ=WEEKLY;BYDAY=MO;INTERVAL=1', $event->getRecurrenceRule());
        $this->assertSame($recurrenceEndDate, $event->getRecurrenceEndDate());
    }
}
