<?php

namespace App\Service\Calendar;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class OAuthTokenManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LoggerInterface $logger,
        private CalendarManager $calendarManager
    ) {
    }

    /**
     * Get the OAuth token for a user and provider
     *
     * @return array|null The token data or null if not found
     */
    public function getToken(User $user, string $providerName): ?array
    {
        $tokens = $user->getOauthTokens() ?? [];
        return $tokens[$providerName] ?? null;
    }

    /**
     * Save an OAuth token for a user and provider
     *
     * @param array $tokenData Should contain 'access_token' and optionally 'refresh_token', 'expires_in', etc.
     */
    public function saveToken(User $user, string $providerName, array $tokenData): void
    {
        $tokens = $user->getOauthTokens() ?? [];
        $tokens[$providerName] = array_merge(
            $tokens[$providerName] ?? [],
            $tokenData,
            ['updated_at' => (new \DateTimeImmutable())->format(\DateTime::ATOM)]
        );
        
        $user->setOauthTokens($tokens);
        
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->logger->info('OAuth token saved', [
                'user_id' => $user->getId(),
                'provider' => $providerName,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save OAuth token', [
                'user_id' => $user->getId(),
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException('Failed to save OAuth token', 0, $e);
        }
    }

    /**
     * Remove an OAuth token for a user and provider
     */
    public function removeToken(User $user, string $providerName): void
    {
        $tokens = $user->getOauthTokens() ?? [];
        
        if (isset($tokens[$providerName])) {
            unset($tokens[$providerName]);
            $user->setOauthTokens($tokens);
            
            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                
                $this->logger->info('OAuth token removed', [
                    'user_id' => $user->getId(),
                    'provider' => $providerName,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to remove OAuth token', [
                    'user_id' => $user->getId(),
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
                
                throw new \RuntimeException('Failed to remove OAuth token', 0, $e);
            }
        }
    }

    /**
     * Get a valid access token, refreshing it if necessary
     *
     * @return array The valid token data
     * @throws \RuntimeException If the token is invalid or cannot be refreshed
     */
    public function getValidToken(User $user, string $providerName): array
    {
        $token = $this->getToken($user, $providerName);
        
        if (!$token) {
            throw new \RuntimeException('No OAuth token found for this provider');
        }
        
        // Check if the token is expired or about to expire (within 5 minutes)
        $expiresAt = isset($token['expires_at']) ? \DateTimeImmutable::createFromFormat(\DateTime::ATOM, $token['expires_at']) : null;
        $now = new \DateTimeImmutable();
        
        if ($expiresAt && $now->modify('+5 minutes') >= $expiresAt) {
            if (empty($token['refresh_token'])) {
                throw new \RuntimeException('Access token expired and no refresh token available');
            }
            
            try {
                // Refresh the token
                $refreshedToken = $this->calendarManager->refreshAccessToken($providerName, $token['refresh_token']);
                
                // Save the new token
                $this->saveToken($user, $providerName, $refreshedToken);
                
                return $refreshedToken;
            } catch (\Exception $e) {
                $this->logger->error('Failed to refresh OAuth token', [
                    'user_id' => $user->getId(),
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
                
                // Remove the invalid token
                $this->removeToken($user, $providerName);
                
                throw new \RuntimeException('Failed to refresh access token. Please re-authenticate.', 0, $e);
            }
        }
        
        return $token;
    }
}
