<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UserModel;

/**
 * @internal
 */
final class UserModelTest extends CIUnitTestCase
{
    private UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a partial mock to avoid database initialization
        $this->userModel = $this->getMockBuilder(UserModel::class)
            ->onlyMethods([]) // Don't mock any methods, just avoid constructor DB issues
            ->disableOriginalConstructor()
            ->getMock();
        
        // Enable calling the actual methods we want to test
        $this->userModel = new class extends UserModel {
            public function __construct() {
                // Skip parent constructor to avoid DB connection
            }
        };
    }

    // Authentication method tests

    public function testValidatePasswordWithValidPassword(): void
    {
        $password = 'testpassword123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $user = ['password_hash' => $hashedPassword];
        
        $this->assertTrue($this->userModel->validatePassword($password, $user));
    }

    public function testValidatePasswordWithInvalidPassword(): void
    {
        $password = 'testpassword123';
        $wrongPassword = 'wrongpassword';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $user = ['password_hash' => $hashedPassword];
        
        $this->assertFalse($this->userModel->validatePassword($wrongPassword, $user));
    }

    public function testValidatePasswordWithHashString(): void
    {
        $password = 'testpassword123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue($this->userModel->validatePassword($password, $hashedPassword));
    }

    public function testValidatePasswordWithEmptyPassword(): void
    {
        $hashedPassword = password_hash('testpassword123', PASSWORD_DEFAULT);
        $user = ['password_hash' => $hashedPassword];
        
        $this->assertFalse($this->userModel->validatePassword('', $user));
    }

    public function testValidatePasswordWithEmptyHash(): void
    {
        $user = ['password_hash' => ''];
        
        $this->assertFalse($this->userModel->validatePassword('testpassword123', $user));
    }

    public function testValidatePasswordWithMissingHash(): void
    {
        $user = [];
        
        $this->assertFalse($this->userModel->validatePassword('testpassword123', $user));
    }

    public function testIsActiveWithActiveUser(): void
    {
        $user = ['is_active' => 1];
        
        $this->assertTrue($this->userModel->isActive($user));
    }

    public function testIsActiveWithInactiveUser(): void
    {
        $user = ['is_active' => 0];
        
        $this->assertFalse($this->userModel->isActive($user));
    }

    public function testIsActiveWithMissingActiveField(): void
    {
        $user = [];
        
        $this->assertFalse($this->userModel->isActive($user));
    }

    // Role checking method tests

    public function testIsSuperAdminWithSuperAdminUser(): void
    {
        $user = ['role' => 'super_admin'];
        
        $this->assertTrue($this->userModel->isSuperAdmin($user));
    }

    public function testIsSuperAdminWithNonSuperAdminUser(): void
    {
        $user = ['role' => 'admin'];
        
        $this->assertFalse($this->userModel->isSuperAdmin($user));
    }

    public function testIsAdminWithAdminUser(): void
    {
        $user = ['role' => 'admin'];
        
        $this->assertTrue($this->userModel->isAdmin($user));
    }

    public function testIsAdminWithNonAdminUser(): void
    {
        $user = ['role' => 'office_staff'];
        
        $this->assertFalse($this->userModel->isAdmin($user));
    }

    public function testIsOfficeStaffWithOfficeStaffUser(): void
    {
        $user = ['role' => 'office_staff'];
        
        $this->assertTrue($this->userModel->isOfficeStaff($user));
    }

    public function testIsOfficeStaffWithNonOfficeStaffUser(): void
    {
        $user = ['role' => 'viewer'];
        
        $this->assertFalse($this->userModel->isOfficeStaff($user));
    }

    public function testCanAccessOfficeAsSuperAdmin(): void
    {
        $user = ['role' => 'super_admin', 'office_id' => 1];
        $officeId = 2;
        
        $this->assertTrue($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeAsAdmin(): void
    {
        $user = ['role' => 'admin', 'office_id' => 1];
        $officeId = 2;
        
        $this->assertTrue($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeAsOfficeStaffWithSameOffice(): void
    {
        $user = ['role' => 'office_staff', 'office_id' => 1];
        $officeId = 1;
        
        $this->assertTrue($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeAsOfficeStaffWithDifferentOffice(): void
    {
        $user = ['role' => 'office_staff', 'office_id' => 1];
        $officeId = 2;
        
        $this->assertFalse($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeAsViewer(): void
    {
        $user = ['role' => 'viewer', 'office_id' => 1];
        $officeId = 1;
        
        $this->assertFalse($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeWithMissingOfficeId(): void
    {
        $user = ['role' => 'office_staff'];
        $officeId = 1;
        
        $this->assertFalse($this->userModel->canAccessOffice($user, $officeId));
    }

    public function testCanAccessOfficeWithMissingRole(): void
    {
        $user = ['office_id' => 1];
        $officeId = 1;
        
        $this->assertFalse($this->userModel->canAccessOffice($user, $officeId));
    }

    // Edge cases and boundary tests

    public function testRoleMethodsWithMissingRole(): void
    {
        $user = [];
        
        $this->assertFalse($this->userModel->isSuperAdmin($user));
        $this->assertFalse($this->userModel->isAdmin($user));
        $this->assertFalse($this->userModel->isOfficeStaff($user));
    }

    public function testValidatePasswordHashRoundTrip(): void
    {
        // Test multiple passwords to ensure hashing works correctly
        $passwords = ['simple', 'Complex123!', 'very_long_password_with_special_chars_@#$%'];
        
        foreach ($passwords as $password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user = ['password_hash' => $hashedPassword];
            
            $this->assertTrue($this->userModel->validatePassword($password, $user), 
                "Password '{$password}' should validate against its hash");
            
            // Verify other passwords don't work
            $this->assertFalse($this->userModel->validatePassword($password . 'x', $user),
                "Modified password should not validate");
        }
    }
}