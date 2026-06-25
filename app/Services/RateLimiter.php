<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\I18n\Time;

/**
 * Rate limiter service for preventing brute force attacks
 * 
 * Implements IP-based rate limiting with configurable attempt limits,
 * time windows, and lockout periods. Uses database storage for
 * persistence across requests and cleanup of expired records.
 */
class RateLimiter
{
    private ConnectionInterface $db;
    
    // Configuration constants
    private const MAX_ATTEMPTS = 5;           // Maximum attempts allowed
    private const WINDOW_MINUTES = 15;        // Time window for attempts (minutes)
    private const LOCKOUT_MINUTES = 30;       // Lockout duration (minutes)
    private const CLEANUP_PROBABILITY = 0.1;  // 10% chance to run cleanup on each call
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Record a failed login attempt
     *
     * @param string $identifier The identifier (IP address or IP+email)
     * @return bool True if attempt was recorded, false if blocked
     */
    public function attempt(string $identifier): bool
    {
        // Clean up expired records occasionally
        $this->maybeCleanup();
        
        // Check if currently blocked
        if ($this->tooManyAttempts($identifier)) {
            return false;
        }

        $now = Time::now();
        $windowStart = $now->subMinutes(self::WINDOW_MINUTES);
        
        // Get existing record within the current window
        $existing = $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('first_attempt_at >=', $windowStart->toDateTimeString())
            ->get()
            ->getRowArray();

        if ($existing) {
            // Increment existing record
            $newAttempts = $existing['attempts'] + 1;
            $blockedUntil = null;
            
            // Check if this attempt exceeds the limit
            if ($newAttempts >= self::MAX_ATTEMPTS) {
                $blockedUntil = $now->addMinutes(self::LOCKOUT_MINUTES)->toDateTimeString();
            }
            
            $this->db->table('rate_limits')
                ->where('id', $existing['id'])
                ->update([
                    'attempts' => $newAttempts,
                    'blocked_until' => $blockedUntil,
                    'updated_at' => $now->toDateTimeString()
                ]);
        } else {
            // Create new record or reset expired one
            $data = [
                'identifier' => $identifier,
                'attempts' => 1,
                'first_attempt_at' => $now->toDateTimeString(),
                'blocked_until' => null,
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString()
            ];
            
            // Use replace to handle existing expired records
            $this->db->table('rate_limits')
                ->replace($data);
        }

        return true;
    }

    /**
     * Check if identifier has too many attempts
     *
     * @param string $identifier The identifier to check
     * @return bool True if blocked, false if allowed
     */
    public function tooManyAttempts(string $identifier): bool
    {
        $now = Time::now();
        
        // Check for active block
        $blocked = $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->where('blocked_until IS NOT NULL')
            ->where('blocked_until >', $now->toDateTimeString())
            ->countAllResults();

        return $blocked > 0;
    }

    /**
     * Clear all attempts for an identifier
     *
     * @param string $identifier The identifier to clear
     * @return void
     */
    public function clear(string $identifier): void
    {
        $this->db->table('rate_limits')
            ->where('identifier', $identifier)
            ->delete();
    }

    /**
     * Get seconds until identifier is unblocked
     *
     * @param string $identifier The identifier to check
     * @return int Seconds until unblocked, 0 if not blocked
     */
    public function availableIn(string $identifier): int
    {
        $now = Time::now();
        
        $record = $this->db->table('rate_limits')
            ->select('blocked_until')
            ->where('identifier', $identifier)
            ->where('blocked_until IS NOT NULL')
            ->where('blocked_until >', $now->toDateTimeString())
            ->get()
            ->getRowArray();

        if (!$record) {
            return 0;
        }

        $blockedUntil = Time::parse($record['blocked_until']);
        $diff = $blockedUntil->getTimestamp() - $now->getTimestamp();
        
        return max(0, $diff);
    }

    /**
     * Get current attempt count for identifier within window
     *
     * @param string $identifier The identifier to check
     * @return int Current attempt count
     */
    public function attempts(string $identifier): int
    {
        $now = Time::now();
        $windowStart = $now->subMinutes(self::WINDOW_MINUTES);
        
        $record = $this->db->table('rate_limits')
            ->select('attempts')
            ->where('identifier', $identifier)
            ->where('first_attempt_at >=', $windowStart->toDateTimeString())
            ->get()
            ->getRowArray();

        return $record ? (int)$record['attempts'] : 0;
    }

    /**
     * Create identifier from IP and optional email
     *
     * @param string $ipAddress The IP address
     * @param string|null $email Optional email for additional tracking
     * @return string The identifier string
     */
    public function createIdentifier(string $ipAddress, ?string $email = null): string
    {
        if ($email) {
            return $ipAddress . ':' . hash('sha256', $email);
        }
        
        return $ipAddress;
    }

    /**
     * Clean up expired rate limit records
     *
     * @return int Number of records cleaned up
     */
    public function cleanup(): int
    {
        $now = Time::now();
        $expiredTime = $now->subMinutes(max(self::WINDOW_MINUTES, self::LOCKOUT_MINUTES) * 2);
        
        // Remove records where both the window has expired and any block has expired
        $deleted = $this->db->table('rate_limits')
            ->where('first_attempt_at <', $expiredTime->toDateTimeString())
            ->groupStart()
                ->where('blocked_until IS NULL')
                ->orWhere('blocked_until <', $now->toDateTimeString())
            ->groupEnd()
            ->delete();
            
        return $deleted;
    }

    /**
     * Randomly trigger cleanup to maintain database hygiene
     *
     * @return void
     */
    private function maybeCleanup(): void
    {
        if (mt_rand() / mt_getrandmax() < self::CLEANUP_PROBABILITY) {
            $this->cleanup();
        }
    }

    /**
     * Get rate limiter configuration
     *
     * @return array{max_attempts: int, window_minutes: int, lockout_minutes: int}
     */
    public function getConfig(): array
    {
        return [
            'max_attempts' => self::MAX_ATTEMPTS,
            'window_minutes' => self::WINDOW_MINUTES,
            'lockout_minutes' => self::LOCKOUT_MINUTES,
        ];
    }
}