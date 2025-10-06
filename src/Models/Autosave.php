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
        'master_content_uuid',
        'content_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'type',
        'featured_image',
        'meta_title',
        'meta_description',
        'meta_keywords'
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
        // Ensure we have a master_content_uuid
        if (!isset($data['master_content_uuid'])) {
            if (isset($data['content_id']) && $data['content_id']) {
                $data['master_content_uuid'] = 'content-' . $data['content_id'];
            } else {
                $data['master_content_uuid'] = $this->generateUUID();
            }
        }
        
        // Check if autosave already exists for this master content UUID
        $existingAutosave = $this->findByMasterUUID($data['master_content_uuid']);
        
        if ($existingAutosave) {
            // Update existing autosave in place
            $updateData = array_intersect_key($data, array_flip($this->fillable));
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $this->updateById($existingAutosave['id'], $updateData);
            return $existingAutosave['id'];
        } else {
            // Create new autosave
            $result = $this->create($data);
            return $result->getId();
        }
    }

    public function findByUUID(string $uuid): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE autosave_uuid = :uuid LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['uuid' => $uuid]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByMasterUUID(string $masterUuid): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE master_content_uuid = :master_uuid LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(['master_uuid' => $masterUuid]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findByContentId(int $contentId, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE content_id = :content_id 
                AND user_id = :user_id 
                LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            'content_id' => $contentId,
            'user_id' => $userId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getLatestForContent(int $contentId, int $userId): ?array
    {
        return $this->findByContentId($contentId, $userId);
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

    public function deleteByMasterUUID(string $masterUuid): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE master_content_uuid = :master_uuid";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute(['master_uuid' => $masterUuid]);
    }

    public function getAllAutosaves(int $userId = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " ORDER BY updated_at DESC";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function updateById(int $id, array $data): bool
    {
        $setParts = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable) || $key === 'updated_at') {
                $setParts[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        
        if (empty($setParts)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id";
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute($params);
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