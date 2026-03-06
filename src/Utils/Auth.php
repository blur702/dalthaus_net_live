<?php

declare(strict_types=1);

namespace CMS\Utils;

use CMS\Utils\Database;

class Auth
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function attempt(string $username, string $password, bool $rememberMe = false): bool
    {
        error_log("[AUTHUTIL] === attempt() START ===");
        error_log("[AUTHUTIL] Username: $username");
        error_log("[AUTHUTIL] Remember me: " . ($rememberMe ? 'YES' : 'NO'));
        error_log("[AUTHUTIL] Session ID before: " . session_id());
        error_log("[AUTHUTIL] Session data before: " . json_encode($_SESSION ?? []));

        error_log("[AUTHUTIL] → Checking if account is locked out...");
        if ($this->isLockedOut($username)) {
            error_log("[AUTHUTIL] ❌ Account is locked out");
            return false;
        }
        error_log("[AUTHUTIL] ✓ Account not locked out");

        error_log("[AUTHUTIL] → Finding user in database...");
        $user = $this->findUser($username);

        if ($user === false) {
            error_log("[AUTHUTIL] ❌ User not found in database");
            $this->recordFailedAttempt($username);
            return false;
        }
        error_log("[AUTHUTIL] ✓ User found: " . json_encode([
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]));

        error_log("[AUTHUTIL] → Verifying password...");
        if (!password_verify($password, $user['password_hash'])) {
            error_log("[AUTHUTIL] ❌ Password verification failed");
            $this->recordFailedAttempt($username);
            return false;
        }
        error_log("[AUTHUTIL] ✓ Password verified successfully");

        error_log("[AUTHUTIL] → Clearing failed attempts...");
        $this->clearFailedAttempts($username);
        error_log("[AUTHUTIL] ✓ Failed attempts cleared");

        error_log("[AUTHUTIL] → Starting session...");
        $this->startSession($user, $rememberMe);
        error_log("[AUTHUTIL] ✓ Session started");

        error_log("[AUTHUTIL] Session ID after: " . session_id());
        error_log("[AUTHUTIL] Session data after: " . json_encode($_SESSION ?? []));
        error_log("[AUTHUTIL] ✓✓✓ attempt() returning TRUE ✓✓✓");
        error_log("[AUTHUTIL] === attempt() END ===");

        return true;
    }

    private function findUser(string $identifier): array|false
    {
        return $this->db->fetchRow(
            'SELECT user_id, username, email, password_hash, created_at 
             FROM users 
             WHERE username = ? OR email = ?',
            [$identifier, $identifier]
        );
    }

    private function startSession(array $user, bool $rememberMe = false): void
    {
        error_log('Headers: ' . json_encode(getallheaders()));
        // Temporarily disabled session_regenerate_id() due to SameSite=Lax cookie issues
        // causing login failures. TODO: Implement lazy session regeneration on next request
        // if (!isset($_SERVER['HTTP_X_TESTING'])) {
        //     session_regenerate_id(true);
        // }

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        $this->generateCsrfToken();

        if ($rememberMe) {
            error_log("Auth::startSession() - Remember me requested");

            $token = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $token);
            error_log("Auth::startSession() - Generated token for user: " . $user['user_id']);

            $this->storeRememberToken((int)$user['user_id'], $hashedToken);
            error_log("Auth::startSession() - Stored token in database");

            $cookieParams = [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => '',
                'secure' => $this->config['secure_cookies'] ?? false,
                'httponly' => true,
                'samesite' => 'Lax'
            ];

            $cookieValue = $user['user_id'] . ':' . $token;
            setcookie('remember_token', $cookieValue, $cookieParams);
            error_log("Auth::startSession() - Set remember cookie: $cookieValue");
        }

        error_log("Auth::startSession() - Setting session data");
        error_log("Auth::startSession() - user_id: " . $_SESSION['user_id']);
        error_log("Auth::startSession() - logged_in: " . var_export($_SESSION['logged_in'], true));
        error_log("Auth::startSession() - session_id: " . session_id());
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            try {
                $this->db->delete('remember_tokens', 'user_id = ?', [$_SESSION['user_id']]);
            } catch (\Exception $e) {
                error_log("Failed to clear remember tokens: " . $e->getMessage());
            }
        }

        $this->clearRememberCookie();
        $_SESSION = [];

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

        session_destroy();
    }

    public function check(): bool
    {
        error_log("Auth::check() - Starting authentication check");
        error_log("Auth::check() - Session ID: " . session_id());
        error_log("Auth::check() - Session logged_in: " . (isset($_SESSION['logged_in']) ? var_export($_SESSION['logged_in'], true) : 'not set'));
        error_log("Auth::check() - Session last_activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'not set'));
        error_log("Auth::check() - Current time: " . date('Y-m-d H:i:s'));
        error_log("Auth::check() - Remember cookie exists: " . (isset($_COOKIE['remember_token']) ? 'yes' : 'no'));

        if (isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
            error_log("Auth::check() - User has active session");

            // Check if session has expired
            if ($this->isSessionExpired()) {
                $lastActivity = $_SESSION['last_activity'] ?? 0;
                $elapsed = time() - $lastActivity;
                $sessionLifetime = $this->config['session_lifetime'] ?? 3600;
                error_log("Auth::check() - Session EXPIRED! Last activity was {$elapsed} seconds ago (lifetime: {$sessionLifetime})");
                $this->logout();
                return false;
            }

            // Update last activity timestamp
            $oldActivity = $_SESSION['last_activity'] ?? 0;
            $_SESSION['last_activity'] = time();
            error_log("Auth::check() - Updated last_activity from " . date('Y-m-d H:i:s', $oldActivity) . " to " . date('Y-m-d H:i:s', $_SESSION['last_activity']));

            error_log("Auth::check() - Session valid, returning true");
            return true;
        }

        if (isset($_COOKIE['remember_token'])) {
            error_log("Auth::check() - No session but remember cookie exists, attempting auto-login");
            $result = $this->attemptRememberLogin();
            error_log("Auth::check() - Remember login result: " . ($result ? 'success' : 'failed'));
            return $result;
        }

        error_log("Auth::check() - No session and no remember cookie, returning false");
        return false;
    }

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

    public function id(): ?int
    {
        return $this->check() ? ($_SESSION['user_id'] ?? null) : null;
    }

    private function isSessionExpired(): bool
    {
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionLifetime = $this->config['session_lifetime'] ?? 3600;
        
        return (time() - $lastActivity) > $sessionLifetime;
    }

    private function recordFailedAttempt(string $identifier): void
    {
        $attempts = $_SESSION['login_attempts'][$identifier] ?? 0;
        $_SESSION['login_attempts'][$identifier] = $attempts + 1;
        $_SESSION['lockout_time'][$identifier] = time();
    }

    private function clearFailedAttempts(string $identifier): void
    {
        unset($_SESSION['login_attempts'][$identifier]);
        unset($_SESSION['lockout_time'][$identifier]);
    }

    public function clearFailedLoginAttempts(string $identifier): void
    {
        $this->clearFailedAttempts($identifier);
    }

    private function isLockedOut(string $identifier): bool
    {
        $attempts = $_SESSION['login_attempts'][$identifier] ?? 0;
        $maxAttempts = $this->config['login_max_attempts'] ?? 5;
        
        if ($attempts < $maxAttempts) {
            return false;
        }

        $lockoutTime = $_SESSION['lockout_time'][$identifier] ?? 0;
        $lockoutDuration = $this->config['login_lockout_time'] ?? 900;
        
        if ((time() - $lockoutTime) > $lockoutDuration) {
            $this->clearFailedAttempts($identifier);
            return false;
        }

        return true;
    }

    public function getRemainingLockoutTime(string $identifier): int
    {
        if (!$this->isLockedOut($identifier)) {
            return 0;
        }

        $lockoutTime = $_SESSION['lockout_time'][$identifier] ?? 0;
        $lockoutDuration = $this->config['login_lockout_time'] ?? 900;
        
        return max(0, $lockoutDuration - (time() - $lockoutTime));
    }

    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['_token'] ?? '';
        
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    public function createUser(string $username, string $email, string $password): int|false
    {
        if (empty($username) || empty($email) || empty($password)) {
            return false;
        }

        if (!$this->isValidPassword($password)) {
            return false;
        }

        if ($this->userExists($username, $email)) {
            return false;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
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

    private function userExists(string $username, string $email): bool
    {
        return $this->db->exists(
            'users',
            'username = ? OR email = ?',
            [$username, $email]
        );
    }

    private function isValidPassword(string $password): bool
    {
        $minLength = $this->config['password_min_length'] ?? 8;
        
        return strlen($password) >= $minLength;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->db->fetchRow(
            'SELECT password_hash FROM users WHERE user_id = ?',
            [$userId]
        );

        if ($user === false) {
            return false;
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        if (!$this->isValidPassword($newPassword)) {
            return false;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updated = $this->db->update(
            'users',
            ['password_hash' => $newPasswordHash],
            'user_id = ?',
            [$userId]
        );

        return $updated > 0;
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['username', 'email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        foreach ($updateData as $field => $value) {
            if ($this->db->exists(
                'users',
                "{$field} = ? AND user_id != ?",
                [$value, $userId]
            )) {
                return false;
            }
        }

        $updated = $this->db->update(
            'users',
            $updateData,
            'user_id = ?',
            [$userId]
        );

        if ($userId === ($_SESSION['user_id'] ?? null)) {
            foreach ($updateData as $field => $value) {
                $_SESSION[$field] = $value;
            }
        }

        return $updated > 0;
    }

    private function storeRememberToken(int $userId, string $hashedToken): void
    {
        try {
            error_log("Auth::storeRememberToken() - Starting for user_id: $userId");

            $deleted = $this->db->delete('remember_tokens', 'user_id = ?', [$userId]);
            error_log("Auth::storeRememberToken() - Deleted $deleted existing tokens");

            $insertId = $this->db->insert('remember_tokens', [
                'user_id' => $userId,
                'token_hash' => $hashedToken,
                'expires_at' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60))
            ]);
            error_log("Auth::storeRememberToken() - Inserted token with ID: $insertId");
            error_log("Auth::storeRememberToken() - SUCCESS");
        } catch (\Exception $e) {
            error_log("Auth::storeRememberToken() - FAILED: " . $e->getMessage());
            error_log("Auth::storeRememberToken() - Stack trace: " . $e->getTraceAsString());
        }
    }

    private function attemptRememberLogin(): bool
    {
        error_log("Auth::attemptRememberLogin() - Starting remember me login attempt");
        $cookie = $_COOKIE['remember_token'] ?? '';
        if (empty($cookie)) {
            error_log("Auth::attemptRememberLogin() - Cookie is empty");
            return false;
        }

        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            error_log("Auth::attemptRememberLogin() - Cookie format invalid: " . $cookie);
            $this->clearRememberCookie();
            return false;
        }

        [$userId, $token] = $parts;
        $hashedToken = hash('sha256', $token);
        error_log("Auth::attemptRememberLogin() - Cookie parsed: user_id=$userId");

        try {
            $tokenData = $this->db->fetchRow(
                'SELECT * FROM remember_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()',
                [(int)$userId, $hashedToken]
            );

            if ($tokenData === false) {
                $this->clearRememberCookie();
                return false;
            }

            $user = $this->db->fetchRow(
                'SELECT user_id, username, email, created_at FROM users WHERE user_id = ?',
                [(int)$userId]
            );

            if ($user === false) {
                $this->clearRememberCookie();
                return false;
            }

            $this->startSessionFromRemember($user);
            return true;

        } catch (\Exception $e) {
            error_log("Remember login failed: " . $e->getMessage());
            $this->clearRememberCookie();
            return false;
        }
    }

    private function startSessionFromRemember(array $user): void
    {
        if (!isset($_SERVER['HTTP_X_TESTING'])) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        $_SESSION['is_admin'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['remembered'] = true;

        $this->generateCsrfToken();
    }

    private function clearRememberCookie(): void
    {
        setcookie('remember_token', '', time() - 3600, '/', '',
                 $this->config['secure_cookies'] ?? false, true);
    }
}
