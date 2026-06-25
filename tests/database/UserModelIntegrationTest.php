<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UserModel;

/**
 * Integration tests for UserModel that require database functionality
 * 
 * @internal
 */
final class UserModelIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    protected $migrate   = true;
    protected $namespace = 'App';
    protected $seed      = \App\Database\Seeds\DatabaseSeeder::class;

    private UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
    }

    public function testUpdateLastLoginWithValidUserId(): void
    {
        // Insert a test user first
        $userData = [
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT),
            'full_name' => 'Test User',
            'role' => 'office_staff',
            'is_active' => 1,
            'office_id' => 1
        ];

        $userId = $this->userModel->insert($userData);
        $this->assertNotFalse($userId);

        // Test updateLastLogin
        $result = $this->userModel->updateLastLogin($userId);
        $this->assertTrue($result);

        // Verify the last_login_at was updated
        $user = $this->userModel->find($userId);
        $this->assertNotNull($user['last_login_at']);
        
        // Check that the timestamp is recent (within last minute)
        $lastLogin = strtotime($user['last_login_at']);
        $now = time();
        $this->assertLessThan(60, $now - $lastLogin, 'Last login should be updated to current time');
    }

    public function testUpdateLastLoginWithInvalidUserId(): void
    {
        // Try to update last login for non-existent user
        $result = $this->userModel->updateLastLogin(999999);
        $this->assertFalse($result);
    }

    protected function getPrivateMethodAsClosure($obj, $method)
    {
        $reflection = new \ReflectionClass(get_class($obj));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->getClosure($obj);
    }

    public function testRequirePasswordHashOnInsertCallback(): void
    {
        // Test the callback method using reflection since it's protected
        $callback = $this->getPrivateMethodAsClosure($this->userModel, 'requirePasswordHashOnInsert');

        // Test with missing password_hash
        $eventData = ['data' => ['email' => 'test@example.com']];
        $result = $callback($eventData);
        $this->assertFalse($result);

        // Test with empty password_hash
        $eventData = ['data' => ['email' => 'test@example.com', 'password_hash' => '']];
        $result = $callback($eventData);
        $this->assertFalse($result);

        // Test with valid password_hash
        $eventData = ['data' => [
            'email' => 'test@example.com',
            'password_hash' => password_hash('password', PASSWORD_DEFAULT)
        ]];
        $result = $callback($eventData);
        $this->assertEquals($eventData, $result);
    }
}