<?php

namespace Tests\Unit\Services;

use App\Services\RateLimiter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\I18n\Time;

/**
 * Test suite for RateLimiter service
 * 
 * Tests brute force protection functionality including
 * attempt tracking, blocking, and cleanup mechanisms.
 */
class RateLimiterTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = null;

    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new RateLimiter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testCreateIdentifierWithIpOnly(): void
    {
        $identifier = $this->rateLimiter->createIdentifier('192.168.1.1');
        $this->assertEquals('192.168.1.1', $identifier);
    }

    public function testCreateIdentifierWithIpAndEmail(): void
    {
        $identifier = $this->rateLimiter->createIdentifier('192.168.1.1', 'test@example.com');
        $expectedHash = hash('sha256', 'test@example.com');
        $this->assertEquals("192.168.1.1:$expectedHash", $identifier);
    }

    public function testFirstAttemptIsAllowed(): void
    {
        $identifier = 'test_ip';
        $result = $this->rateLimiter->attempt($identifier);
        
        $this->assertTrue($result);
        $this->assertFalse($this->rateLimiter->tooManyAttempts($identifier));
        $this->assertEquals(1, $this->rateLimiter->attempts($identifier));
    }

    public function testMultipleAttemptsWithinLimit(): void
    {
        $identifier = 'test_ip';
        
        // Make 4 attempts (under the limit of 5)
        for ($i = 1; $i <= 4; $i++) {
            $result = $this->rateLimiter->attempt($identifier);
            $this->assertTrue($result, "Attempt $i should be allowed");
            $this->assertFalse($this->rateLimiter->tooManyAttempts($identifier));
            $this->assertEquals($i, $this->rateLimiter->attempts($identifier));
        }
    }

    public function testExceedingAttemptLimitBlocksUser(): void
    {
        $identifier = 'test_ip';
        
        // Make exactly 5 attempts (the limit)
        for ($i = 1; $i <= 5; $i++) {
            $result = $this->rateLimiter->attempt($identifier);
            $this->assertTrue($result, "Attempt $i should be recorded");
        }
        
        // Should now be blocked
        $this->assertTrue($this->rateLimiter->tooManyAttempts($identifier));
        $this->assertEquals(5, $this->rateLimiter->attempts($identifier));
        
        // Additional attempts should be rejected
        $result = $this->rateLimiter->attempt($identifier);
        $this->assertFalse($result);
    }

    public function testBlockedUserHasAvailableInTime(): void
    {
        $identifier = 'test_ip';
        
        // Exceed limit to get blocked
        for ($i = 1; $i <= 5; $i++) {
            $this->rateLimiter->attempt($identifier);
        }
        
        $availableIn = $this->rateLimiter->availableIn($identifier);
        
        // Should be blocked for approximately 30 minutes (1800 seconds)
        $this->assertGreaterThan(1700, $availableIn); // Allow some margin for test execution time
        $this->assertLessThanOrEqual(1800, $availableIn);
    }

    public function testClearRemovesAllAttempts(): void
    {
        $identifier = 'test_ip';
        
        // Make some attempts
        for ($i = 1; $i <= 3; $i++) {
            $this->rateLimiter->attempt($identifier);
        }
        
        $this->assertEquals(3, $this->rateLimiter->attempts($identifier));
        
        // Clear attempts
        $this->rateLimiter->clear($identifier);
        
        $this->assertEquals(0, $this->rateLimiter->attempts($identifier));
        $this->assertFalse($this->rateLimiter->tooManyAttempts($identifier));
    }

    public function testGetConfigReturnsCorrectValues(): void
    {
        $config = $this->rateLimiter->getConfig();
        
        $this->assertIsArray($config);
        $this->assertEquals(5, $config['max_attempts']);
        $this->assertEquals(15, $config['window_minutes']);
        $this->assertEquals(30, $config['lockout_minutes']);
    }

    public function testCleanupRemovesExpiredRecords(): void
    {
        $identifier = 'test_ip';
        
        // Make some attempts
        $this->rateLimiter->attempt($identifier);
        
        // Verify record exists
        $this->assertEquals(1, $this->rateLimiter->attempts($identifier));
        
        // Manually set the record to be expired by updating the database
        $db = \Config\Database::connect();
        $expiredTime = Time::now()->subHours(2)->toDateTimeString();
        
        $db->table('rate_limits')
            ->where('identifier', $identifier)
            ->update(['first_attempt_at' => $expiredTime]);
        
        // Run cleanup
        $cleanedUp = $this->rateLimiter->cleanup();
        
        // Should have cleaned up at least 1 record
        $this->assertGreaterThanOrEqual(1, $cleanedUp);
        
        // Attempts should now be 0 (record was removed)
        $this->assertEquals(0, $this->rateLimiter->attempts($identifier));
    }

    public function testDifferentIdentifiersAreTrackedSeparately(): void
    {
        $identifier1 = 'ip1';
        $identifier2 = 'ip2';
        
        // Make attempts with first identifier
        $this->rateLimiter->attempt($identifier1);
        $this->rateLimiter->attempt($identifier1);
        
        // Make attempt with second identifier
        $this->rateLimiter->attempt($identifier2);
        
        // Should track separately
        $this->assertEquals(2, $this->rateLimiter->attempts($identifier1));
        $this->assertEquals(1, $this->rateLimiter->attempts($identifier2));
        
        // Neither should be blocked yet
        $this->assertFalse($this->rateLimiter->tooManyAttempts($identifier1));
        $this->assertFalse($this->rateLimiter->tooManyAttempts($identifier2));
    }

    public function testNonBlockedUserHasZeroAvailableInTime(): void
    {
        $identifier = 'test_ip';
        
        // Make a few attempts but not enough to block
        $this->rateLimiter->attempt($identifier);
        $this->rateLimiter->attempt($identifier);
        
        $availableIn = $this->rateLimiter->availableIn($identifier);
        
        $this->assertEquals(0, $availableIn);
    }
}