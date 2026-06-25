<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Services\RateLimiter;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    private UserModel $userModel;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rateLimiter = new RateLimiter();
        helper(['form', 'url']);
    }

    /**
     * Display the login form
     *
     * @return string The login form view
     */
    public function login(): string
    {
        // If user is already logged in, redirect to admin dashboard
        if (session()->has('user_id')) {
            return redirect()->to('/admin/dashboard')->send();
        }

        $data = [
            'title' => 'Admin Login',
            'validation' => session()->getFlashdata('validation'),
            'error' => session()->getFlashdata('error'),
        ];

        $content = view('auth/login', $data);
        
        return view('layouts/public', [
            'title' => $data['title'],
            'content' => $content
        ]);
    }

    /**
     * Process login attempt and authenticate user
     *
     * @return ResponseInterface Redirect response
     */
    public function authenticate(): ResponseInterface
    {
        // Get client IP address
        $ipAddress = $this->request->getIPAddress();
        $email = $this->request->getPost('email') ?? '';
        
        // Create identifier for rate limiting (IP + email hash)
        $identifier = $this->rateLimiter->createIdentifier($ipAddress, $email);
        
        // Check if this IP/email combination is rate limited
        if ($this->rateLimiter->tooManyAttempts($identifier)) {
            $availableIn = $this->rateLimiter->availableIn($identifier);
            $minutes = ceil($availableIn / 60);
            
            return redirect()->back()
                ->withInput()
                ->with('error', "Too many failed login attempts. Please try again in {$minutes} minutes.");
        }



        // Validation rules
        $rules = [
            'email' => 'required|valid_email|max_length[255]',
            'password' => 'required|min_length[1]|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $password = $this->request->getPost('password');

        // Find user by email
        $user = $this->userModel->where('email', $email)->first();

        // Validate credentials and account status
        if (!$user) {
            // Record failed attempt for non-existent user
            $this->rateLimiter->attempt($identifier);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        if (!$this->userModel->isActive($user)) {
            // Record failed attempt for inactive user
            $this->rateLimiter->attempt($identifier);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Your account has been deactivated. Please contact an administrator.');
        }

        if (!$this->userModel->validatePassword($password, $user)) {
            // Record failed attempt for invalid password
            $this->rateLimiter->attempt($identifier);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid email or password.');
        }

        // Check if user has appropriate role for admin access
        $allowedRoles = ['super_admin', 'admin', 'office_staff'];
        if (!in_array($user['role'], $allowedRoles)) {
            // Record failed attempt for insufficient role
            $this->rateLimiter->attempt($identifier);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'You do not have permission to access the admin area.');
        }

        // Clear rate limiting on successful login
        $this->rateLimiter->clear($identifier);

        // Regenerate session ID for security
        session()->regenerate();

        // Store user information in session
        $sessionData = [
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'user_name' => $user['full_name'],
            'user_role' => $user['role'],
            'office_id' => $user['office_id'],
            'logged_in' => true,
            'login_time' => time()
        ];

        session()->set($sessionData);

        // Update last login timestamp
        $this->userModel->updateLastLogin($user['id']);

        // Redirect to admin dashboard
        return redirect()->to('/admin/dashboard')
            ->with('success', 'Welcome back, ' . $user['full_name'] . '!');
    }

    /**
     * Handle user logout and session destruction
     *
     * @return ResponseInterface Redirect response
     */
    public function logout(): ResponseInterface
    {
        // Destroy the session completely
        session()->destroy();

        // Redirect to login page with success message
        return redirect()->to('/auth/login')
            ->with('success', 'You have been successfully logged out.');
    }

    /**
     * Check if user is authenticated (helper method)
     *
     * @return bool True if user is authenticated, false otherwise
     */
    public function isAuthenticated(): bool
    {
        return session()->has('user_id') && session()->get('logged_in') === true;
    }

    /**
     * Get current authenticated user data (helper method)
     *
     * @return array|null User data or null if not authenticated
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => session()->get('user_id'),
            'email' => session()->get('user_email'),
            'full_name' => session()->get('user_name'),
            'role' => session()->get('user_role'),
            'office_id' => session()->get('office_id'),
            'login_time' => session()->get('login_time')
        ];
    }

    /**
     * Get rate limiting status for current IP/email
     *
     * @param string|null $email Optional email to check specific combination
     * @return array Rate limiting status information
     */
    public function getRateLimitStatus(?string $email = null): array
    {
        $ipAddress = $this->request->getIPAddress();
        $identifier = $this->rateLimiter->createIdentifier($ipAddress, $email);
        
        $isBlocked = $this->rateLimiter->tooManyAttempts($identifier);
        $attempts = $this->rateLimiter->attempts($identifier);
        $availableIn = $isBlocked ? $this->rateLimiter->availableIn($identifier) : 0;
        $config = $this->rateLimiter->getConfig();
        
        return [
            'is_blocked' => $isBlocked,
            'attempts' => $attempts,
            'max_attempts' => $config['max_attempts'],
            'available_in_seconds' => $availableIn,
            'available_in_minutes' => ceil($availableIn / 60),
            'remaining_attempts' => max(0, $config['max_attempts'] - $attempts),
        ];
    }
}