<?php

namespace App\Tests\Service;

use App\Entity\Habit;
use App\Entity\HabitCompletion;
use App\Entity\User;
use App\Repository\HabitCompletionRepository;
use App\Repository\HabitRepository;
use App\Service\Habit\HabitService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HabitServiceTest extends TestCase
{
    private HabitService $habitService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|HabitRepository $habitRepository;
    private MockObject|HabitCompletionRepository $habitCompletionRepository;
    private MockObject|LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->habitRepository = $this->createMock(HabitRepository::class);
        $this->habitCompletionRepository = $this->createMock(HabitCompletionRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->habitService = new HabitService(
            $this->entityManager,
            $this->habitRepository,
            $this->habitCompletionRepository,
            $this->logger
        );
    }

    public function testCreateHabit(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $habit = new Habit();
        $habit->setName('Test Habit');
        $habit->setTargetFrequency(5);

        // Expect the entity manager to persist and flush the habit
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($habit));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        // The logger should be called with an info message
        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->habitService->createHabit($habit, $user);
        
        $this->assertSame($user, $habit->getUser());
        $this->assertSame($habit, $result);
    }

    public function testToggleHabitCompletionAddsNewCompletion(): void
    {
        $habit = new Habit();
        $date = new \DateTimeImmutable('2023-04-01');
        
        // Mock the repository to return null (no existing completion)
        $this->habitCompletionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);
            
        // Expect the entity manager to persist a new completion
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($completion) use ($habit, $date) {
                return $completion instanceof HabitCompletion
                    && $completion->getHabit() === $habit
                    && $completion->getCompletionDate()->format('Y-m-d') === $date->format('Y-m-d');
            }));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        $this->habitService->toggleHabitCompletion($habit, $date);
    }

    public function testToggleHabitCompletionRemovesExistingCompletion(): void
    {
        $habit = new Habit();
        $date = new \DateTimeImmutable('2023-04-01');
        $existingCompletion = new HabitCompletion();
        
        // Mock the repository to return an existing completion
        $this->habitCompletionRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($existingCompletion);
            
        // Expect the entity manager to remove the existing completion
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($existingCompletion));
            
        $this->entityManager->expects($this->once())
            ->method('flush');
            
        $this->habitService->toggleHabitCompletion($habit, $date);
    }

    public function testGetHabitStatistics(): void
    {
        $user = new User();
        $startDate = new \DateTimeImmutable('2023-04-01');
        $endDate = new \DateTimeImmutable('2023-04-07'); // One week
        
        // Create some test habits
        $activeHabit1 = new Habit();
        $activeHabit1->setName('Active 1')->setIsActive(true);
        
        $activeHabit2 = new Habit();
        $activeHabit2->setName('Active 2')->setIsActive(true);
        
        $inactiveHabit = new Habit();
        $inactiveHabit->setName('Inactive')->setIsActive(false);
        
        // Mock the repository to return our test habits
        $this->habitRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([$activeHabit1, $activeHabit2, $inactiveHabit]);
            
        // Mock the completion repository to return some completions
        $completion1 = new HabitCompletion();
        $completion1->setHabit($activeHabit1);
        $completion1->setCompletionDate(new \DateTimeImmutable('2023-04-02'));
        
        $completion2 = new HabitCompletion();
        $completion2->setHabit($activeHabit2);
        $completion2->setCompletionDate(new \DateTimeImmutable('2023-04-02'));
        
        $this->habitCompletionRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturnCallback(function () use ($completion1, $completion2) {
                $mockQuery = $this->createMock(\Doctrine\ORM\Query::class);
                $mockQuery->expects($this->once())
                    ->method('getResult')
                    ->willReturn([$completion1, $completion2]);
                
                $mockQueryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
                $mockQueryBuilder->method('join')->willReturnSelf();
                $mockQueryBuilder->method('where')->willReturnSelf();
                $mockQueryBuilder->method('andWhere')->willReturnSelf();
                $mockQueryBuilder->method('setParameter')->willReturnSelf();
                $mockQueryBuilder->method('getQuery')->willReturn($mockQuery);
                
                return $mockQueryBuilder;
            });
        
        // Call the method under test
        $stats = $this->habitService->getHabitStatistics($user, $startDate, $endDate);
        
        // Assert the statistics are calculated correctly
        $this->assertEquals(3, $stats['total_habits']);
        $this->assertEquals(2, $stats['active_habits']);
        $this->assertGreaterThan(0, $stats['completion_rate']);
        $this->assertArrayHasKey('2023-04-02', $stats['completion_history']);
        $this->assertCount(2, $stats['completion_history']['2023-04-02']);
    }
}
