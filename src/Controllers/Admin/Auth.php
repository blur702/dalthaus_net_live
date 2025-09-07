<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Utils\Auth as AuthUtil;
use CMS\Utils\Security;

/**
 * Admin Authentication Controller
 * 
 * Handles admin login/logout functionality with security features.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Auth extends BaseController
{
    /**
     * Authentication utility
     */
    private AuthUtil $auth;

    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        $this->auth = new AuthUtil($this->db, $this->config['security']);
        $this->view->layout('auth');
    }

    /**
     * Show login form
     * 
     * @return void
     */
    public function login(): void
    {
        // Redirect if already logged in
        if ($this->auth->check()) {
            $this->redirect('/admin/dashboard');
        }

        // Get any flash messages
        $flash = $this->getFlash();
        
        // Generate CSRF token
        $csrfToken = $this->auth->generateCsrfToken();

        $this->render('admin/auth/login', [
            'csrf_token' => $csrfToken,
            'flash' => $flash,
            'page_title' => 'Admin Login'
        ]);
    }

    /**
     * Process login attempt
     * 
     * @return void
     */
    public function authenticate(): void
    {
        // Check if already logged in
        if ($this->auth->check()) {
            $this->redirect('/admin/dashboard');
        }

        // Validate request method
        if (!$this->isPost()) {
            $this->redirect('/admin/login');
        }

        // Validate CSRF token
        if (!$this->auth->validateCsrfToken($this->getParam('_token', '', 'post'))) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/login');
        }

        // Get form data
        $username = $this->sanitize($this->getParam('username', '', 'post'));
        $password = $this->getParam('password', '', 'post');

        // Validate required fields
        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Username and password are required.');
            $this->redirect('/admin/login');
        }

        // Rate limiting check
        $rateLimitKey = 'admin_login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!Security::checkRateLimit($rateLimitKey, 5, 300)) { // 5 attempts per 5 minutes
            $this->setFlash('error', 'Too many login attempts. Please wait 5 minutes before trying again.');
            $this->redirect('/admin/login');
        }

        // Check if user is locked out
        $remainingLockout = $this->auth->getRemainingLockoutTime($username);
        if ($remainingLockout > 0) {
            $minutes = ceil($remainingLockout / 60);
            $this->setFlash('error', "Account locked due to failed login attempts. Please wait {$minutes} minute(s).");
            $this->redirect('/admin/login');
        }

        // Attempt authentication
        if ($this->auth->attempt($username, $password)) {
            // Successful login
            $this->setFlash('success', 'Welcome back!');
            $this->redirect('/admin/dashboard');
        } else {
            // Failed login
            $remainingLockout = $this->auth->getRemainingLockoutTime($username);
            if ($remainingLockout > 0) {
                $minutes = ceil($remainingLockout / 60);
                $this->setFlash('error', "Invalid credentials. Account locked for {$minutes} minute(s).");
            } else {
                $this->setFlash('error', 'Invalid username or password.');
            }
            
            $this->redirect('/admin/login');
        }
    }

    /**
     * Process logout
     * 
     * @return void
     */
    public function logout(): void
    {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            $this->redirect('/admin/login');
        }

        // Validate CSRF token for POST requests
        if ($this->isPost()) {
            if (!$this->auth->validateCsrfToken($this->getParam('_token', '', 'post'))) {
                $this->setFlash('error', 'Invalid security token.');
                $this->redirect('/admin/dashboard');
            }
        }

        // Logout user
        $this->auth->logout();
        
        $this->setFlash('success', 'You have been logged out successfully.');
        $this->redirect('/admin/login');
    }
}
