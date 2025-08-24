<?php

namespace App\Service\Calendar;

use App\Entity\CalendarEvent;
use App\Entity\Habit;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

class CalendarManager
{
    private ServiceLocator $providers;
    private array $enabledProviders;

    public function __construct(
        ServiceLocator $providers,
        array $enabledProviders = []
    ) {
        $this->providers = $providers;
        $this->enabledProviders = $enabledProviders;
    }

    /**
     * Get a list of enabled calendar providers
     *
     * @return string[] Array of provider names that are enabled
     */
    public function getEnabledProviders(): array
    {
        return $this->enabledProviders;
    }

    /**
     * Get a specific calendar provider
     *
     * @throws \InvalidArgumentException If the provider is not enabled or does not exist
     */
    public function getProvider(string $providerName): CalendarProviderInterface
    {
        if (!in_array($providerName, $this->enabledProviders, true)) {
            throw new \InvalidArgumentException(sprintf('Calendar provider "%s" is not enabled.', $providerName));
        }

        if (!$this->providers->has($providerName)) {
            throw new \InvalidArgumentException(sprintf('Calendar provider "%s" does not exist.', $providerName));
        }

        return $this->providers->get($providerName);
    }

    /**
     * Get the authorization URL for a provider
     */
    public function getAuthorizationUrl(string $providerName, string $redirectUri, array $scopes = []): string
    {
        return $this->getProvider($providerName)->getAuthorizationUrl($redirectUri, $scopes);
    }

    /**
     * Handle the OAuth callback for a provider
     *
     * @return array The token data from the provider
     */
    public function handleCallback(string $providerName, Request $request, string $redirectUri): array
    {
        return $this->getProvider($providerName)->handleCallback($request, $redirectUri);
    }

    /**
     * Create a calendar event for a habit
     */
    public function createEvent(string $providerName, Habit $habit, array $tokenData): CalendarEvent
    {
        return $this->getProvider($providerName)->createEvent($habit, $tokenData);
    }

    /**
     * Update an existing calendar event
     */
    public function updateEvent(string $providerName, CalendarEvent $calendarEvent, array $tokenData): CalendarEvent
    {
        return $this->getProvider($providerName)->updateEvent($calendarEvent, $tokenData);
    }

    /**
     * Delete a calendar event
     */
    public function deleteEvent(string $providerName, CalendarEvent $calendarEvent, array $tokenData): bool
    {
        return $this->getProvider($providerName)->deleteEvent($calendarEvent, $tokenData);
    }

    /**
     * Refresh an expired access token
     *
     * @return array The new token data
     */
    public function refreshAccessToken(string $providerName, string $refreshToken): array
    {
        return $this->getProvider($providerName)->refreshAccessToken($refreshToken);
    }
}
