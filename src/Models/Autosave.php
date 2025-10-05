<?php

namespace App\Models;

use CMS\Models\BaseModel;
use PDO;

class Autosave extends BaseModel
{
    protected string $table = 'autosaves';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'autosave_uuid',
        'content_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'type',
        'featured_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'version_number'
    ];
    
    private $pdo;
    
    private function getPdo(): PDO
    {
        if (!$this->pdo) {
            $this->pdo = $this->db->getConnection();
        }
        return $this->pdo;
    }

    public function createOrUpdate(array $data): ?int
    {
        $existingAutosave = $this->findByUUID($data['autosave_uuid']);
        
        if ($existingAutosave) {
            // Check if we've reached the limit of 3 versions
            $versionCount = $this->getVersionCount($data['autosave_uuid']);
            
            if ($versionCount >= 3) {
                // Delete the oldest version
                $this->deleteOldestVersion($data['autosave_uuid']);
            }
            
            // Increment version number for new save
            $data['version_number'] = $this->getLatestVersionNumber($data['autosave_uuid']) + 1;
            
            // Create new version
            $result = $this->create($data);
            return $result->getId();
        } else {
            // First autosave for this UUID
            $data['version_number'] = 1;
            $result = $this->create($data);
            return $result->getId();
        }
    }

    public function findByUUID(string $uuid): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE autosave_uuid = :uuid ORDER BY version_number DESC LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findAllByUUID(string $uuid): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE autosave_uuid = :uuid ORDER BY version_number DESC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByContentId(int $contentId, int $userId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE content_id = :content_id 
                AND user_id = :user_id 
                ORDER BY updated_at DESC";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'content_id' => $contentId,
            'user_id' => $userId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestForContent(int $contentId, int $userId): ?array
    {
        $sql = "SELECT a1.* FROM {$this->table} a1
                INNER JOIN (
                    SELECT autosave_uuid, MAX(version_number) as max_version
                    FROM {$this->table}
                    WHERE content_id = :content_id AND user_id = :user_id
                    GROUP BY autosave_uuid
                ) a2 ON a1.autosave_uuid = a2.autosave_uuid 
                AND a1.version_number = a2.max_version
                WHERE a1.content_id = :content_id2 
                AND a1.user_id = :user_id2
                ORDER BY a1.updated_at DESC";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'content_id' => $contentId,
            'user_id' => $userId,
            'content_id2' => $contentId,
            'user_id2' => $userId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function deleteByContentId(int $contentId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE content_id = :content_id";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute(['content_id' => $contentId]);
    }

    public function deleteByUUID(string $uuid): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE autosave_uuid = :uuid";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute(['uuid' => $uuid]);
    }

    private function getVersionCount(string $uuid): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE autosave_uuid = :uuid";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    private function getLatestVersionNumber(string $uuid): int
    {
        $sql = "SELECT MAX(version_number) as max_version FROM {$this->table} WHERE autosave_uuid = :uuid";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['max_version'] ?? 0);
    }

    private function deleteOldestVersion(string $uuid): bool
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE autosave_uuid = :uuid 
                ORDER BY version_number ASC 
                LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute(['uuid' => $uuid]);
    }

    public function cleanupOldAutosaves(int $daysOld = 30): int
    {
        $sql = "DELETE FROM {$this->table} 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['days' => $daysOld]);
        
        return $stmt->rowCount();
    }
}