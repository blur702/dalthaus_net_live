<?php

declare(strict_types=1);

namespace CMS\Utils;

use CMS\Utils\Database;

/**
 * Authentication Class
 * 
 * Handles user authentication, session management, and security features
 * including login attempts tracking and CSRF protection.
 * 
 * @package CMS\Utils
 * @author  Kevin
 * @version 1.0.0
 */
class Auth
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Security configuration
     */
    private array $config;

    /**
     * Constructor
     * 
     * @param Database $db Database instance
     * @param array $config Security configuration
     */
    public function __construct(Database $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Attempt to authenticate user
     * 
     * @param string $username Username or email
     * @param string $password Password
     * @return bool True if authentication successful
     */
    public function attempt(string $username, string $password): bool
    {
        // Check for login lockout
        if ($this->isLockedOut($username)) {
            return false;
        }

        // Find user by username or email
        $user = $this->findUser($username);
        
        if ($user === false) {
            $this->recordFailedAttempt($username);
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($username);
            return false;
        }

        // Clear failed attempts on successful login
        $this->clearFailedAttempts($username);
        
        // Start user session
        $this->startSession($user);
        
        return true;
    }

    /**
     * Find user by username or email
     * 
     * @param string $identifier Username or email
     * @return array|false User data or false if not found
     */
    private function findUser(string $identifier): array|false
    {
        return $this->db->fetchRow(
            'SELECT user_id, username, email, password_hash, created_at 
             FROM users 
             WHERE username = ? OR email = ?',
            [$identifier, $identifier]
        );
    }

    /**
     * Start user session
     * 
     * @param array $user User data
     * @return void
     */
    private function startSession(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store user data in session
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Generate CSRF token
        $this->generateCsrfToken();
    }

    /**
     * Log out current user
     * 
     * @return void
     */
    public function logout(): void
    {
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(), 
                '', 
                time() - 3600, 
                '/', 
                '', 
                $this->config['secure_cookies'] ?? false, 
                true
            );
        }
        
        // Destroy session
        session_destroy();
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public function check(): bool
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        // Check session timeout
        if ($this->isSessionExpired()) {
            $this->logout();
            return false;
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Get current user data
     * 
     * @return array|null
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->check() ? ($_SESSION['user_id'] ?? null) : null;
    }

    /**
     * Check if session has expired
     * 
     * @return bool
     */
    private function isSessionExpired(): bool
    {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionLifetime = $this->config['session_lifetime'] ?? 3600;
        
        return (time() - $lastActivity) > $sessionLifetime;
    }

    /**
     * Record failed login attempt
     * 
     * @param string $identifier Username or email
     * @return void
     */
    private function recordFailedAttempt(string $identifier): void
    {
        $attempts = $_SESSION['login_attempts'][$identifier] ?? 0;
        $_SESSION['login_attempts'][$identifier] = $attempts + 1;
        $_SESSION['lockout_time'][$identifier] = time();
    }

    /**
     * Clear failed login attempts
     * 
     * @param string $identifier Username or email
     * @return void
     */
    private function clearFailedAttempts(string $identifier): void
    {
        unset($_SESSION['login_attempts'][$identifier]);
        unset($_SESSION['lockout_time'][$identifier]);
    }

    /**
     * Clear failed login attempts (public alias)
     * 
     * @param string $identifier Username or email
     * @return void
     */
    public function clearFailedLoginAttempts(string $identifier): void
    {
        $this->clearFailedAttempts($identifier);
    }

    /**
     * Check if user is locked out
     * 
     * @param string $identifier Username or email
     * @return bool
     */
    private function isLockedOut(string $identifier): bool
    {
        $attempts = $_SESSION['login_attempts'][$identifier] ?? 0;
        $maxAttempts = $this->config['login_max_attempts'] ?? 5;
        
        if ($attempts < $maxAttempts) {
            return false;
        }

        $lockoutTime = $_SESSION['lockout_time'][$identifier] ?? 0;
        $lockoutDuration = $this->config['login_lockout_time'] ?? 900;
        
        // Check if lockout period has expired
        if ((time() - $lockoutTime) > $lockoutDuration) {
            $this->clearFailedAttempts($identifier);
            return false;
        }

        return true;
    }

    /**
     * Get remaining lockout time in seconds
     * 
     * @param string $identifier Username or email
     * @return int
     */
    public function getRemainingLockoutTime(string $identifier): int
    {
        if (!$this->isLockedOut($identifier)) {
            return 0;
        }

        $lockoutTime = $_SESSION['lockout_time'][$identifier] ?? 0;
        $lockoutDuration = $this->config['login_lockout_time'] ?? 900;
        
        return max(0, $lockoutDuration - (time() - $lockoutTime));
    }

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_token'];
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool
     */
    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['_token'] ?? '';
        
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    /**
     * Create new user (admin only)
     * 
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Password
     * @return int|false User ID or false on error
     */
    public function createUser(string $username, string $email, string $password): int|false
    {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return false;
        }

        // Check password strength
        if (!$this->isValidPassword($password)) {
            return false;
        }

        // Check if username or email already exists
        if ($this->userExists($username, $email)) {
            return false;
        }

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert user
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash
            ]);

            return (int) $userId;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user exists
     * 
     * @param string $username Username
     * @param string $email Email
     * @return bool
     */
    private function userExists(string $username, string $email): bool
    {
        return $this->db->exists(
            'users',
            'username = ? OR email = ?',
            [$username, $email]
        );
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return bool
     */
    private function isValidPassword(string $password): bool
    {
        $minLength = $this->config['password_min_length'] ?? 8;
        
        return strlen($password) >= $minLength;
    }

    /**
     * Change user password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        // Get user data
        $user = $this->db->fetchRow(
            'SELECT password_hash FROM users WHERE user_id = ?',
            [$userId]
        );

        if ($user === false) {
            return false;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        // Validate new password
        if (!$this->isValidPassword($newPassword)) {
            return false;
        }

        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $updated = $this->db->update(
            'users',
            ['password_hash' => $newPasswordHash],
            'user_id = ?',
            [$userId]
        );

        return $updated > 0;
    }

    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $data Profile data
     * @return bool
     */
    public function updateProfile(int $userId, array $data): bool
    {
        // Filter allowed fields
        $allowedFields = ['username', 'email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        // Check for duplicate username/email
        foreach ($updateData as $field => $value) {
            if ($this->db->exists(
                'users',
                "{$field} = ? AND user_id != ?",
                [$value, $userId]
            )) {
                return false;
            }
        }

        // Update user
        $updated = $this->db->update(
            'users',
            $updateData,
            'user_id = ?',
            [$userId]
        );

        // Update session data if current user
        if ($userId === ($_SESSION['user_id'] ?? null)) {
            foreach ($updateData as $field => $value) {
                $_SESSION[$field] = $value;
            }
        }

        return $updated > 0;
    }
}
