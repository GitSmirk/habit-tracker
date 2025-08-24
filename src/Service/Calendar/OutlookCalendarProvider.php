<?php

namespace App\Service\Calendar;

use App\Entity\{CalendarEvent, Habit};
use Microsoft\Graph\{Graph, Model\Event as OutlookEvent, Model\DateTimeTimeZone, Model\ItemBody};
use Symfony\Component\HttpFoundation\Request;

class OutlookCalendarProvider implements CalendarProviderInterface
{
    private const SCOPES = ['offline_access', 'Calendars.ReadWrite'];
    private Graph $graphClient;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $tenantId,
        private string $redirectPath
    ) {
        $this->graphClient = new Graph();
    }

    public function getName(): string { return 'outlook'; }

    public function getAuthorizationUrl(string $redirectUri, array $scopes = []): string
    {
        $scopes = !empty($scopes) ? $scopes : self::SCOPES;
        $scopes[] = 'offline_access';
        
        return "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/authorize?" . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => bin2hex(random_bytes(16)),
        ]);
    }

    public function handleCallback(Request $request, string $redirectUri): array
    {
        $code = $request->query->get('code');
        if (!$code) throw new \RuntimeException('No auth code');
        
        $response = $this->httpRequest('POST', 
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ]
            ]
        );
        
        if (isset($response['error'])) {
            throw new \RuntimeException($response['error_description'] ?? 'Auth error');
        }
        
        $response['expires_at'] = (new \DateTimeImmutable())
            ->add(new \DateInterval('PT' . ($response['expires_in'] - 60) . 'S'))
            ->format(\DateTime::ATOM);
            
        return $response;
    }

    public function createEvent(Habit $habit, array $tokenData): CalendarEvent
    {
        $this->graphClient->setAccessToken($tokenData['access_token']);
        
        $event = new OutlookEvent();
        $event->setSubject($habit->getName());
        
        $body = new ItemBody();
        $body->setContent($habit->getDescription() ?? '');
        $body->setContentType('text');
        $event->setBody($body);
        
        $startTime = new \DateTimeImmutable();
        $endTime = $startTime->add(new \DateInterval('PT1H'));
        
        $start = $this->createDateTimeZone($startTime);
        $end = $this->createDateTimeZone($endTime);
        
        $event->setStart($start);
        $event->setEnd($end);
        
        $createdEvent = $this->graphClient
            ->createRequest('POST', '/me/events')
            ->attachBody($event)
            ->setReturnType(OutlookEvent::class)
            ->execute();
        
        return $this->mapToCalendarEvent($createdEvent, $habit);
    }

    public function updateEvent(CalendarEvent $calendarEvent, array $tokenData): CalendarEvent
    {
        $this->graphClient->setAccessToken($tokenData['access_token']);
        
        $event = new OutlookEvent();
        $event->setSubject($calendarEvent->getTitle());
        
        $body = new ItemBody();
        $body->setContent($calendarEvent->getDescription() ?? '');
        $body->setContentType('text');
        $event->setBody($body);
        
        $start = $this->createDateTimeZone($calendarEvent->getStartDate());
        $end = $this->createDateTimeZone($calendarEvent->getEndDate());
        
        $event->setStart($start);
        $event->setEnd($end);
        
        $updatedEvent = $this->graphClient
            ->createRequest('PATCH', "/me/events/{$calendarEvent->getExternalId()}")
            ->attachBody($event)
            ->setReturnType(OutlookEvent::class)
            ->execute();
        
        return $this->mapToCalendarEvent($updatedEvent, $calendarEvent->getHabit(), $calendarEvent);
    }

    public function deleteEvent(CalendarEvent $calendarEvent, array $tokenData): bool
    {
        $this->graphClient->setAccessToken($tokenData['access_token']);
        
        try {
            $this->graphClient
                ->createRequest('DELETE', "/me/events/{$calendarEvent->getExternalId()}")
                ->execute();
            return true;
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), '404')) throw $e;
            return true;
        }
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $response = $this->httpRequest('POST', 
            "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
            [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ]
            ]
        );
        
        if (isset($response['error'])) {
            throw new \RuntimeException($response['error_description'] ?? 'Token refresh failed');
        }
        
        $response['expires_at'] = (new \DateTimeImmutable())
            ->add(new \DateInterval('PT' . ($response['expires_in'] - 60) . 'S'))
            ->format(\DateTime::ATOM);
            
        return $response;
    }
    
    private function createDateTimeZone(\DateTimeInterface $dateTime): DateTimeTimeZone
    {
        $dtz = new DateTimeTimeZone();
        $dtz->setDateTime($dateTime->format('Y-m-d\TH:i:s'));
        $dtz->setTimeZone('UTC');
        return $dtz;
    }
    
    private function mapToCalendarEvent(
        OutlookEvent $outlookEvent, 
        Habit $habit, 
        ?CalendarEvent $calendarEvent = null
    ): CalendarEvent {
        $calendarEvent = $calendarEvent ?? new CalendarEvent();
        $calendarEvent->setHabit($habit);
        $calendarEvent->setTitle($outlookEvent->getSubject());
        $calendarEvent->setDescription($outlookEvent->getBody()?->getContent() ?? '');
        $calendarEvent->setStartDate(new \DateTimeImmutable($outlookEvent->getStart()->getDateTime()));
        $calendarEvent->setEndDate(new \DateTimeImmutable($outlookEvent->getEnd()->getDateTime()));
        $calendarEvent->setExternalId($outlookEvent->getId());
        $calendarEvent->setExternalSource($this->getName());
        
        return $calendarEvent;
    }
    
    private function httpRequest(string $method, string $url, array $options = []): array
    {
        $client = new \GuzzleHttp\Client();
        
        try {
            $response = $client->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true) ?: [];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $body = $response ? json_decode($response->getBody()->getContents(), true) : [];
            throw new \RuntimeException(
                $body['error_description'] ?? $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
