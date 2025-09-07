<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * Activity Log Model
 * 
 * Handles activity tracking for admin dashboard and audit purposes.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class ActivityLog extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'activity_logs';

    /**
     * Primary key
     */
    protected string $primaryKey = 'log_id';

    /**
     * Activity types
     */
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';
    public const TYPE_DELETE = 'delete';
    public const TYPE_LOGIN = 'login';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_PUBLISH = 'publish';
    public const TYPE_UNPUBLISH = 'unpublish';
    public const TYPE_VIEW = 'view';

    /**
     * Entity types
     */
    public const ENTITY_CONTENT = 'content';
    public const ENTITY_PAGE = 'page';
    public const ENTITY_USER = 'user';
    public const ENTITY_SETTING = 'setting';
    public const ENTITY_MENU = 'menu';

    /**
     * Log an activity
     * 
     * @param int $userId User ID
     * @param string $action Action type (create, update, delete, etc.)
     * @param string $entityType Entity type (content, page, user, etc.)
     * @param int|null $entityId Entity ID
     * @param string|null $description Optional description
     * @param array|null $metadata Optional metadata as JSON
     * @return bool
     */
    public static function log(
        int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $metadata = null
    ): bool {
        $instance = new static();
        
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $instance->createRecord($data) !== null;
    }

    /**
     * Get recent activity with user information
     * 
     * @param int $limit Number of activities to retrieve
     * @param array $filters Optional filters
     * @return array
     */
    public static function getRecentActivity(int $limit = 50, array $filters = []): array
    {
        $instance = new static();
        
        $query = "SELECT al.*, u.username 
                  FROM {$instance->table} al 
                  LEFT JOIN users u ON al.user_id = u.user_id";
        
        $params = [];
        $whereClauses = [];

        // User filter
        if (!empty($filters['user_id'])) {
            $whereClauses[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }

        // Action filter
        if (!empty($filters['action'])) {
            $whereClauses[] = "al.action = ?";
            $params[] = $filters['action'];
        }

        // Entity type filter
        if (!empty($filters['entity_type'])) {
            $whereClauses[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereClauses[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }

        // Add WHERE clause
        if (!empty($whereClauses)) {
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $query .= " ORDER BY al.created_at DESC LIMIT {$limit}";

        $instance = new static();
        return $instance->db->fetchAll($query, $params);
    }

    /**
     * Get activity statistics for dashboard
     * 
     * @param string $period Period (today, week, month)
     * @return array
     */
    public static function getActivityStats(string $period = 'today'): array
    {
        $instance = new static();
        
        $dateCondition = match($period) {
            'today' => "DATE(created_at) = CURDATE()",
            'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            default => "DATE(created_at) = CURDATE()"
        };

        $query = "SELECT 
                    action,
                    COUNT(*) as count
                  FROM {$instance->table} 
                  WHERE {$dateCondition}
                  GROUP BY action
                  ORDER BY count DESC";

        $instance = new static();
        return $instance->db->fetchAll($query);
    }

    /**
     * Get user activity summary
     * 
     * @param int $userId User ID
     * @param int $days Number of days to look back
     * @return array
     */
    public static function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $instance = new static();
        
        $query = "SELECT 
                    action,
                    entity_type,
                    COUNT(*) as count,
                    MAX(created_at) as last_activity
                  FROM {$instance->table} 
                  WHERE user_id = ? 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY action, entity_type
                  ORDER BY count DESC";

        $instance = new static();
        return $instance->db->fetchAll($query, [$userId, $days]);
    }

    /**
     * Get most active users
     * 
     * @param int $limit Number of users to return
     * @param int $days Number of days to look back
     * @return array
     */
    public static function getMostActiveUsers(int $limit = 10, int $days = 30): array
    {
        $instance = new static();
        
        $query = "SELECT 
                    al.user_id,
                    u.username,
                    COUNT(*) as activity_count,
                    MAX(al.created_at) as last_activity
                  FROM {$instance->table} al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY al.user_id, u.username
                  ORDER BY activity_count DESC
                  LIMIT {$limit}";

        $instance = new static();
        return $instance->db->fetchAll($query, [$days]);
    }

    /**
     * Clean up old activity logs
     * 
     * @param int $daysToKeep Number of days to keep
     * @return int Number of deleted records
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $instance = new static();
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        return $instance->db->delete(
            $instance->table,
            'created_at < ?',
            [$cutoffDate]
        );
    }

    /**
     * Get formatted action label
     * 
     * @return string
     */
    public function getFormattedAction(): string
    {
        $action = $this->getAttribute('action');
        $entityType = $this->getAttribute('entity_type');
        
        return match($action) {
            self::TYPE_CREATE => "Created {$entityType}",
            self::TYPE_UPDATE => "Updated {$entityType}",
            self::TYPE_DELETE => "Deleted {$entityType}",
            self::TYPE_PUBLISH => "Published {$entityType}",
            self::TYPE_UNPUBLISH => "Unpublished {$entityType}",
            self::TYPE_LOGIN => "Logged in",
            self::TYPE_LOGOUT => "Logged out",
            self::TYPE_VIEW => "Viewed {$entityType}",
            default => ucfirst($action) . " {$entityType}"
        };
    }

    /**
     * Get formatted time difference
     * 
     * @return string
     */
    public function getTimeAgo(): string
    {
        $createdAt = $this->getAttribute('created_at');
        if (!$createdAt) {
            return '';
        }

        $timestamp = strtotime($createdAt);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $timestamp);
        }
    }

    /**
     * Get action icon class
     * 
     * @return string
     */
    public function getActionIcon(): string
    {
        return match($this->getAttribute('action')) {
            self::TYPE_CREATE => 'text-green-500',
            self::TYPE_UPDATE => 'text-blue-500',
            self::TYPE_DELETE => 'text-red-500',
            self::TYPE_PUBLISH => 'text-purple-500',
            self::TYPE_UNPUBLISH => 'text-gray-500',
            self::TYPE_LOGIN => 'text-green-500',
            self::TYPE_LOGOUT => 'text-gray-500',
            self::TYPE_VIEW => 'text-indigo-500',
            default => 'text-gray-400'
        };
    }
}
