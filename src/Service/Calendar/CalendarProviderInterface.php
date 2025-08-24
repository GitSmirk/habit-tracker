<?php

namespace App\Service\Calendar;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CalendarProviderInterface
{
    /**
     * Get the name of the calendar provider (e.g., 'google', 'outlook')
     */
    public function getName(): string;

    /**
     * Generate an authorization URL for OAuth flow
     */
    public function getAuthorizationUrl(string $redirectUri, array $scopes = []): string;

    /**
     * Handle the OAuth callback and return an access token
     * 
     * @return array The token data including 'access_token', 'refresh_token', 'expires_in', etc.
     */
    public function handleCallback(Request $request, string $redirectUri): array;

    /**
     * Create a new calendar event
     * 
     * @param Habit $habit The habit to create an event for
     * @param array $tokenData The OAuth token data
     * @return CalendarEvent The created calendar event
     */
    public function createEvent(Habit $habit, array $tokenData): CalendarEvent;

    /**
     * Update an existing calendar event
     */
    public function updateEvent(CalendarEvent $calendarEvent, array $tokenData): CalendarEvent;

    /**
     * Delete a calendar event
     */
    public function deleteEvent(CalendarEvent $calendarEvent, array $tokenData): bool;

    /**
     * Refresh an expired access token
     * 
     * @param string $refreshToken The refresh token
     * @return array The new token data
     */
    public function refreshAccessToken(string $refreshToken): array;
}
