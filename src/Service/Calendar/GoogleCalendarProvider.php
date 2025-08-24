<?php

namespace App\Service\Calendar;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class GoogleCalendarProvider implements CalendarProviderInterface
{
    private const SCOPES = [
        Calendar::CALENDAR, // Manage calendars
        Calendar::CALENDAR_EVENTS, // View and edit events
    ];

    public function __construct(
        private Client $googleClient,
        private RouterInterface $router,
        private string $clientId,
        private string $clientSecret,
        private string $applicationName
    ) {
        $this->googleClient->setApplicationName($this->applicationName);
        $this->googleClient->setClientId($this->clientId);
        $this->googleClient->setClientSecret($this->secret);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('select_account consent');
    }

    public function getName(): string
    {
        return 'google';
    }

    public function getAuthorizationUrl(string $redirectUri, array $scopes = []): string
    {
        $scopes = !empty($scopes) ? $scopes : self::SCOPES;
        
        $this->googleClient->setRedirectUri($redirectUri);
        $this->googleClient->setScopes($scopes);
        
        return $this->googleClient->createAuthUrl();
    }

    public function handleCallback(Request $request, string $redirectUri): array
    {
        $this->googleClient->setRedirectUri($redirectUri);
        
        $code = $request->query->get('code');
        if (!$code) {
            throw new \RuntimeException('No authorization code received from Google');
        }
        
        $token = $this->googleClient->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            throw new \RuntimeException('Error from Google: ' . json_encode($token));
        }
        
        return $token;
    }

    public function createEvent(Habit $habit, array $tokenData): CalendarEvent
    {
        $this->googleClient->setAccessToken($tokenData);
        
        $service = new Calendar($this->googleClient);
        
        // Create a Google Calendar event based on the habit
        $event = new GoogleEvent([
            'summary' => $habit->getName(),
            'description' => $habit->getDescription() ?? '',
            'start' => [
                'dateTime' => (new \DateTime())->format(\DateTime::RFC3339),
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => (new \DateTime('+1 hour'))->format(\DateTime::RFC3339),
                'timeZone' => 'UTC',
            ],
            'reminders' => [
                'useDefault' => true,
            ],
        ]);
        
        // Set recurrence if the habit has a frequency
        if ($habit->getTargetFrequency() > 0) {
            $event->setRecurrence([
                'RRULE:FREQ=WEEKLY;COUNT=52', // Weekly for a year
            ]);
        }
        
        try {
            $calendarId = 'primary'; // Use the primary calendar
            $createdEvent = $service->events->insert($calendarId, $event);
            
            // Create and return our CalendarEvent entity
            $calendarEvent = new CalendarEvent();
            $calendarEvent->setHabit($habit);
            $calendarEvent->setTitle($createdEvent->getSummary());
            $calendarEvent->setDescription($createdEvent->getDescription());
            $calendarEvent->setStartDate(new \DateTimeImmutable($createdEvent->getStart()->getDateTime()));
            $calendarEvent->setEndDate(new \DateTimeImmutable($createdEvent->getEnd()->getDateTime()));
            $calendarEvent->setExternalId($createdEvent->getId());
            $calendarEvent->setExternalSource($this->getName());
            
            if (!empty($createdEvent->getRecurrence())) {
                $calendarEvent->setRecurrenceRule(implode(';', $createdEvent->getRecurrence()));
            }
            
            return $calendarEvent;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to create Google Calendar event: ' . $e->getMessage(), 0, $e);
        }
    }

    public function updateEvent(CalendarEvent $calendarEvent, array $tokenData): CalendarEvent
    {
        $this->googleClient->setAccessToken($tokenData);
        $service = new Calendar($this->googleClient);
        
        try {
            $event = $service->events->get('primary', $calendarEvent->getExternalId());
            
            // Update the event properties
            $event->setSummary($calendarEvent->getTitle());
            $event->setDescription($calendarEvent->getDescription());
            
            $start = new EventDateTime();
            $start->setDateTime($calendarEvent->getStartDate()->format(\DateTime::RFC3339));
            $event->setStart($start);
            
            $end = new EventDateTime();
            $end->setDateTime($calendarEvent->getEndDate()->format(\DateTime::RFC3339));
            $event->setEnd($end);
            
            if ($calendarEvent->isRecurring()) {
                $event->setRecurrence([$calendarEvent->getRecurrenceRule()]);
            }
            
            $updatedEvent = $service->events->update('primary', $event->getId(), $event);
            
            // Update our CalendarEvent entity with the latest data
            $calendarEvent->setTitle($updatedEvent->getSummary());
            $calendarEvent->setDescription($updatedEvent->getDescription());
            $calendarEvent->setStartDate(new \DateTimeImmutable($updatedEvent->getStart()->getDateTime()));
            $calendarEvent->setEndDate(new \DateTimeImmutable($updatedEvent->getEnd()->getDateTime()));
            
            return $calendarEvent;
            
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to update Google Calendar event: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteEvent(CalendarEvent $calendarEvent, array $tokenData): bool
    {
        $this->googleClient->setAccessToken($tokenData);
        $service = new Calendar($this->googleClient);
        
        try {
            $service->events->delete('primary', $calendarEvent->getExternalId());
            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to delete Google Calendar event: ' . $e->getMessage(), 0, $e);
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        try {
            $this->googleClient->fetchAccessTokenWithRefreshToken($refreshToken);
            return $this->googleClient->getAccessToken();
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to refresh Google access token: ' . $e->getMessage(), 0, $e);
        }
    }
}
