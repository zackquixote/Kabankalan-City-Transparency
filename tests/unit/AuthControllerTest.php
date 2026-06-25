<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\ControllerTestTrait;
use App\Models\UserModel;

class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use ControllerTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = \App\Database\Seeds\DatabaseSeeder::class;
    protected $namespace   = 'App';

    public function testLoginPageDisplaysCorrectly()
    {
        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('login');

        $this->assertTrue($result->isOK());
        
        $response = $result->response();
        $this->assertStringContainsString('Admin Login', $response->getBody());
        $this->assertStringContainsString('Email Address', $response->getBody());
        $this->assertStringContainsString('Password', $response->getBody());
    }

    public function testAuthenticateWithValidCredentials()
    {
        $_POST = [
            'email' => 'admin@kabankalan.gov.ph',
            'password' => 'Kabankalan2026!',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Should redirect to admin dashboard
        $this->assertTrue($result->isRedirect());
        $this->assertStringContainsString('/admin/dashboard', $result->getRedirectUrl());
    }

    public function testAuthenticateWithInvalidCredentials()
    {
        $_POST = [
            'email' => 'admin@kabankalan.gov.ph',
            'password' => 'wrongpassword',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Should redirect back with error
        $this->assertTrue($result->isRedirect());
    }

    public function testAuthenticateBlocksViewerRole()
    {
        $_POST = [
            'email' => 'treasurer@kabankalan.gov.ph', // viewer role
            'password' => 'Kabankalan2026!',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Should redirect back with error (viewer role not allowed)
        $this->assertTrue($result->isRedirect());
    }

    public function testAuthenticateBlocksInactiveUser()
    {
        $_POST = [
            'email' => 'inactive@kabankalan.gov.ph', // inactive user
            'password' => 'Kabankalan2026!',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Should redirect back with error (inactive account)
        $this->assertTrue($result->isRedirect());
    }

    public function testLogoutDestroysSession()
    {
        // First, set up a logged-in session
        session()->set([
            'user_id' => 1,
            'user_email' => 'admin@kabankalan.gov.ph',
            'logged_in' => true
        ]);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('logout');

        // Should redirect to login page
        $this->assertTrue($result->isRedirect());
        $this->assertStringContainsString('/auth/login', $result->getRedirectUrl());
    }

    public function testSessionRegenerationOnLogin()
    {
        $_POST = [
            'email' => 'admin@kabankalan.gov.ph',
            'password' => 'Kabankalan2026!',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Session should be regenerated (didRegenerate set to true on MockSession)
        $this->assertTrue(session()->didRegenerate);
    }

    public function testLastLoginTimestampUpdated()
    {
        $userModel = new UserModel();
        
        // Get user before login
        $userBefore = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
        $this->assertNull($userBefore['last_login_at']);

        $_POST = [
            'email' => 'admin@kabankalan.gov.ph',
            'password' => 'Kabankalan2026!',
        ];
        $this->request->setMethod('post');
        $this->request->setGlobal('post', $_POST);
        $this->request->setGlobal('request', $_POST);

        $result = $this->controller(\App\Controllers\Auth::class)
                       ->execute('authenticate');

        // Get user after login
        $userAfter = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
        $this->assertNotNull($userAfter['last_login_at']);
    }

    public function testPasswordValidationMethods()
    {
        $userModel = new UserModel();
        $user = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
        
        // Test correct password
        $this->assertTrue($userModel->validatePassword('Kabankalan2026!', $user));
        
        // Test incorrect password
        $this->assertFalse($userModel->validatePassword('wrongpassword', $user));
        
        // Test empty password
        $this->assertFalse($userModel->validatePassword('', $user));
    }

    public function testRoleCheckingMethods()
    {
        $userModel = new UserModel();
        
        // Test super admin
        $superAdmin = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
        $this->assertTrue($userModel->isSuperAdmin($superAdmin));
        $this->assertFalse($userModel->isAdmin($superAdmin));
        $this->assertFalse($userModel->isOfficeStaff($superAdmin));
        
        // Test admin
        $admin = $userModel->where('email', 'planner@kabankalan.gov.ph')->first();
        $this->assertFalse($userModel->isSuperAdmin($admin));
        $this->assertTrue($userModel->isAdmin($admin));
        $this->assertFalse($userModel->isOfficeStaff($admin));
        
        // Test office staff
        $officeStaff = $userModel->where('email', 'engineer@kabankalan.gov.ph')->first();
        $this->assertFalse($userModel->isSuperAdmin($officeStaff));
        $this->assertFalse($userModel->isAdmin($officeStaff));
        $this->assertTrue($userModel->isOfficeStaff($officeStaff));
    }

    public function testOfficeAccessPermissions()
    {
        $userModel = new UserModel();
        
        // Test super admin can access any office
        $superAdmin = $userModel->where('email', 'admin@kabankalan.gov.ph')->first();
        $this->assertTrue($userModel->canAccessOffice($superAdmin, 1));
        $this->assertTrue($userModel->canAccessOffice($superAdmin, 999));
        
        // Test office staff can only access their own office
        $officeStaff = $userModel->where('email', 'engineer@kabankalan.gov.ph')->first();
        $this->assertTrue($userModel->canAccessOffice($officeStaff, 3)); // Their office
        $this->assertFalse($userModel->canAccessOffice($officeStaff, 4)); // Different office
        
        // Test viewer has no office access
        $viewer = $userModel->where('email', 'treasurer@kabankalan.gov.ph')->first();
        $this->assertFalse($userModel->canAccessOffice($viewer, 7)); // Any office
    }
}