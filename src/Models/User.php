<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * User Model
 * 
 * Handles user authentication, management, and relationships
 * with content and other user-related operations.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class User extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'users';

    /**
     * Primary key
     */
    protected string $primaryKey = 'user_id';

    /**
     * Find user by username
     * 
     * @param string $username Username
     * @return static|null
     */
    public static function findByUsername(string $username): ?static
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table} WHERE username = ?";
        return self::queryFirst($query, [$username]);
    }

    /**
     * Find user by email
     * 
     * @param string $email Email address
     * @return static|null
     */
    public static function findByEmail(string $email): ?static
    {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table} WHERE email = ?";
        return self::queryFirst($query, [$email]);
    }

    /**
     * Create new user with hashed password
     * 
     * @param array $data User data (must include 'password')
     * @return static
     * @throws \InvalidArgumentException When password is missing
     */
    public static function createUser(array $data): static
    {
        if (empty($data['password'])) {
            throw new \InvalidArgumentException('Password is required');
        }

        // Hash the password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']); // Remove plain password

        // Set created_at if not provided
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        return self::create($data);
    }

    /**
     * Verify user password
     * 
     * @param string $password Plain text password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        $hash = $this->getAttribute('password_hash');
        return $hash && password_verify($password, $hash);
    }

    /**
     * Update user password
     * 
     * @param string $newPassword New plain text password
     * @return bool
     */
    public function updatePassword(string $newPassword): bool
    {
        $this->setAttribute('password_hash', password_hash($newPassword, PASSWORD_DEFAULT));
        return $this->save();
    }

    /**
     * Check if username is available
     * 
     * @param string $username Username to check
     * @param int|null $excludeUserId Exclude user ID (for updates)
     * @return bool
     */
    public static function isUsernameAvailable(string $username, ?int $excludeUserId = null): bool
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} WHERE username = ?";
        $params = [$username];

        if ($excludeUserId !== null) {
            $query .= " AND user_id != ?";
            $params[] = $excludeUserId;
        }

        $count = (int) $instance->db->fetchColumn($query, $params);
        return $count === 0;
    }

    /**
     * Check if email is available
     * 
     * @param string $email Email to check
     * @param int|null $excludeUserId Exclude user ID (for updates)
     * @return bool
     */
    public static function isEmailAvailable(string $email, ?int $excludeUserId = null): bool
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} WHERE email = ?";
        $params = [$email];

        if ($excludeUserId !== null) {
            $query .= " AND user_id != ?";
            $params[] = $excludeUserId;
        }

        $count = (int) $instance->db->fetchColumn($query, $params);
        return $count === 0;
    }

    /**
     * Get user's content count by type
     * 
     * @param string|null $contentType Content type filter
     * @param string|null $status Status filter
     * @return int
     */
    public function getContentCount(?string $contentType = null, ?string $status = null): int
    {
        $query = "SELECT COUNT(*) FROM content WHERE user_id = ?";
        $params = [$this->getAttribute('user_id')];

        if ($contentType !== null) {
            $query .= " AND content_type = ?";
            $params[] = $contentType;
        }

        if ($status !== null) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        return (int) $this->db->fetchColumn($query, $params);
    }

    /**
     * Get user's recent content
     * 
     * @param int $limit Number of items to retrieve
     * @return array
     */
    public function getRecentContent(int $limit = 5): array
    {
        $query = "SELECT content_id, title, content_type, status, updated_at 
                  FROM content 
                  WHERE user_id = ? 
                  ORDER BY updated_at DESC 
                  LIMIT ?";

        return $this->db->fetchAll($query, [$this->getAttribute('user_id'), $limit]);
    }

    /**
     * Get all users for admin list
     * 
     * @param array $filters Filter parameters
     * @param int|null $limit Limit
     * @param int|null $offset Offset
     * @return array
     */
    public static function getForAdmin(array $filters = [], ?int $limit = null, ?int $offset = null): array
    {
        $instance = new static();
        
        $query = "SELECT u.*, 
                         COUNT(c.content_id) as content_count,
                         COUNT(CASE WHEN c.status = 'published' THEN 1 END) as published_count
                  FROM {$instance->table} u 
                  LEFT JOIN content c ON u.user_id = c.user_id";
        
        $params = [];
        $whereClauses = [];

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $query .= " GROUP BY u.user_id";

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        $query .= " ORDER BY u.{$sortBy} {$sortDir}";

        // Pagination
        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        $results = self::query($query, $params);
        
        // Convert model objects to arrays for views
        return array_map(function($user) {
            if (is_object($user)) {
                return $user->toArray();
            }
            return $user;
        }, $results);
    }

    /**
     * Count users for admin with filters
     * 
     * @param array $filters Filter parameters
     * @return int
     */
    public static function countForAdmin(array $filters = []): int
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table} u";
        $params = [];
        $whereClauses = [];

        // Search filter
        if (!empty($filters['search'])) {
            $whereClauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        return (int) $instance->db->fetchColumn($query, $params);
    }

    /**
     * Get formatted created date
     * 
     * @param string $format Date format
     * @return string
     */
    public function getFormattedCreatedDate(string $format = 'F j, Y'): string
    {
        $createdAt = $this->getAttribute('created_at');
        return $createdAt ? date($format, strtotime($createdAt)) : '';
    }

    /**
     * Get user display name (username)
     * 
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->getAttribute('username') ?? 'Unknown';
    }

    /**
     * Check if user has content
     * 
     * @return bool
     */
    public function hasContent(): bool
    {
        return $this->getContentCount() > 0;
    }

    /**
     * Validate user data
     * 
     * @param array $data User data to validate
     * @param int|null $excludeUserId User ID to exclude (for updates)
     * @return array Array of validation errors (empty if valid)
     */
    public static function validateUserData(array $data, ?int $excludeUserId = null): array
    {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters long';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Username must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, underscores, and hyphens';
        } elseif (!self::isUsernameAvailable($data['username'], $excludeUserId)) {
            $errors['username'] = 'Username is already taken';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        } elseif (strlen($data['email']) > 100) {
            $errors['email'] = 'Email must be less than 100 characters';
        } elseif (!self::isEmailAvailable($data['email'], $excludeUserId)) {
            $errors['email'] = 'Email is already taken';
        }

        // Password validation (only for new users or when password is provided)
        if ($excludeUserId === null || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 8) {
                $errors['password'] = 'Password must be at least 8 characters long';
            }
        }

        return $errors;
    }

    /**
     * Get safe user data (without password hash)
     * 
     * @return array
     */
    public function getSafeData(): array
    {
        $data = $this->toArray();
        unset($data['password_hash']);
        return $data;
    }
}
