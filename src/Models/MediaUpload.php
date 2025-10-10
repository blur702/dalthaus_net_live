<?php

declare(strict_types=1);

namespace CMS\Models;

use PDO;

class MediaUpload extends BaseModel
{
    protected string $table = 'media_uploads';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'filename',
        'filepath',
        'original_filename',
        'file_size',
        'file_type',
        'upload_type',
        'user_id',
        'content_id',
        'used_in_content'
    ];

    /**
     * Track a new media upload
     */
    public static function track(array $data): ?int
    {
        $model = new static();
        $result = $model->create($data);
        return $result ? $result->getId() : null;
    }

    /**
     * Get all uploads by user
     */
    public static function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "SELECT * FROM media_uploads
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get uploads by content ID
     */
    public static function getByContent(int $contentId): array
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "SELECT * FROM media_uploads
                WHERE content_id = :content_id
                ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['content_id' => $contentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get unused uploads (not linked to any content)
     */
    public static function getUnused(int $daysOld = 7): array
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "SELECT * FROM media_uploads
                WHERE used_in_content = FALSE
                AND content_id IS NULL
                AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['days' => $daysOld]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark upload as used in content
     */
    public static function markAsUsed(string $filepath, ?int $contentId = null): bool
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "UPDATE media_uploads
                SET used_in_content = TRUE";

        if ($contentId) {
            $sql .= ", content_id = :content_id";
        }

        $sql .= " WHERE filepath = :filepath";

        $stmt = $pdo->prepare($sql);
        $params = ['filepath' => $filepath];

        if ($contentId) {
            $params['content_id'] = $contentId;
        }

        return $stmt->execute($params);
    }

    /**
     * Get upload statistics
     */
    public static function getStats(): array
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "SELECT
                    COUNT(*) as total_uploads,
                    SUM(file_size) as total_size,
                    COUNT(CASE WHEN used_in_content = TRUE THEN 1 END) as used_uploads,
                    COUNT(CASE WHEN used_in_content = FALSE THEN 1 END) as unused_uploads,
                    upload_type,
                    COUNT(*) as count_by_type
                FROM media_uploads
                GROUP BY upload_type";

        $stmt = $pdo->query($sql);
        $typeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total stats
        $sql = "SELECT
                    COUNT(*) as total_uploads,
                    SUM(file_size) as total_size,
                    COUNT(CASE WHEN used_in_content = TRUE THEN 1 END) as used_uploads,
                    COUNT(CASE WHEN used_in_content = FALSE THEN 1 END) as unused_uploads
                FROM media_uploads";

        $stmt = $pdo->query($sql);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'totals' => $totals,
            'by_type' => $typeStats
        ];
    }

    /**
     * Delete orphaned uploads (files that don't exist on disk)
     */
    public static function deleteOrphaned(): int
    {
        $model = new static();
        $pdo = $model->db->getConnection();

        $sql = "SELECT * FROM media_uploads";
        $stmt = $pdo->query($sql);
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $deleted = 0;
        $uploadPath = dirname(__DIR__, 2) . '/uploads/';

        foreach ($uploads as $upload) {
            $fullPath = $uploadPath . ltrim($upload['filepath'], '/');

            if (!file_exists($fullPath)) {
                $deleteSql = "DELETE FROM media_uploads WHERE id = :id";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute(['id' => $upload['id']]);
                $deleted++;
            }
        }

        return $deleted;
    }
}
