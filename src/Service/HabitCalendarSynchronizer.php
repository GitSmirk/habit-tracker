<?php

namespace App\Service;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use App\Entity\User;
use App\Repository\CalendarEventRepository;
use App\Service\Calendar\CalendarManager;
use App\Service\Calendar\OAuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HabitCalendarSynchronizer
{
    public function __construct(
        private CalendarManager $calendarManager,
        private OAuthTokenManager $tokenManager,
        private CalendarEventRepository $calendarEventRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
        private string $appBaseUrl
    ) {
    }

    /**
     * Sync a habit with the connected calendar
     * 
     * This will create, update, or remove calendar events as needed
     */
    public function syncHabit(Habit $habit, bool $force = false): void
    {
        $user = $habit->getUser();
        
        // Get the calendar provider (e.g., 'google' or 'outlook')
        $providerName = $user->getCalendarProvider();
        
        if (!$providerName) {
            // No calendar provider configured for this user
            return;
        }
        
        try {
            $token = $this->tokenManager->getValidToken($user, $providerName);
            
            // Find existing calendar events for this habit
            $existingEvents = $this->calendarEventRepository->findBy(['habit' => $habit]);
            
            if ($habit->isActive() && $habit->getSyncWithCalendar()) {
                // Habit is active and should be synced with calendar
                $this->createOrUpdateHabitEvent($habit, $providerName, $token, $existingEvents);
            } else {
                // Habit is inactive or shouldn't be synced - remove any existing events
                $this->removeHabitEvents($habit, $providerName, $token, $existingEvents);
            }
            
            $this->entityManager->flush();
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync habit with calendar', [
                'habit_id' => $habit->getId(),
                'user_id' => $user->getId(),
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Create or update a calendar event for a habit
     */
    private function createOrUpdateHabitEvent(
        Habit $habit,
        string $providerName,
        array $token,
        array $existingEvents
    ): void {
        $calendarEvent = null;
        
        // Try to find an existing event to update
        foreach ($existingEvents as $event) {
            if ($event->getExternalSource() === $providerName) {
                $calendarEvent = $event;
                break;
            }
        }
        
        try {
            if ($calendarEvent) {
                // Update existing event
                $updatedEvent = $this->calendarManager->updateEvent(
                    $providerName,
                    $calendarEvent,
                    $token
                );
                
                $this->logger->info('Updated calendar event for habit', [
                    'habit_id' => $habit->getId(),
                    'event_id' => $calendarEvent->getId(),
                    'provider' => $providerName,
                ]);
            } else {
                // Create new event
                $calendarEvent = $this->calendarManager->createEvent(
                    $providerName,
                    $habit,
                    $token
                );
                
                $this->entityManager->persist($calendarEvent);
                
                $this->logger->info('Created calendar event for habit', [
                    'habit_id' => $habit->getId(),
                    'event_id' => $calendarEvent->getId(),
                    'provider' => $providerName,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync habit with calendar', [
                'habit_id' => $habit->getId(),
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Remove all calendar events for a habit
     */
    private function removeHabitEvents(
        Habit $habit,
        string $providerName,
        array $token,
        array $existingEvents
    ): void {
        foreach ($existingEvents as $calendarEvent) {
            try {
                if ($calendarEvent->getExternalSource() === $providerName) {
                    $this->calendarManager->deleteEvent($providerName, $calendarEvent, $token);
                    $this->entityManager->remove($calendarEvent);
                    
                    $this->logger->info('Removed calendar event for habit', [
                        'habit_id' => $habit->getId(),
                        'event_id' => $calendarEvent->getId(),
                        'provider' => $providerName,
                    ]);
                }
            } catch (\Exception $e) {
                // Log the error but continue with other events
                $this->logger->error('Failed to remove calendar event', [
                    'habit_id' => $habit->getId(),
                    'event_id' => $calendarEvent->getId(),
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    /**
     * Get the OAuth authorization URL for a calendar provider
     */
    public function getAuthorizationUrl(string $providerName): string
    {
        $redirectUri = $this->urlGenerator->generate('calendar_oauth_callback', [
            'provider' => $providerName,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->calendarManager->getAuthorizationUrl($providerName, $redirectUri);
    }
    
    /**
     * Handle the OAuth callback for a calendar provider
     * 
     * @return array The token data
     */
    public function handleOAuthCallback(string $providerName, Request $request): array
    {
        $redirectUri = $this->urlGenerator->generate('calendar_oauth_callback', [
            'provider' => $providerName,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $tokenData = $this->calendarManager->handleCallback($providerName, $request, $redirectUri);
        
        // Add expiration time if not present
        if (isset($tokenData['expires_in']) && !isset($tokenData['expires_at'])) {
            $expiresAt = new \DateTimeImmutable(sprintf('+%d seconds', $tokenData['expires_in'] - 60));
            $tokenData['expires_at'] = $expiresAt->format(\DateTime::ATOM);
        }
        
        return $tokenData;
    }
}
