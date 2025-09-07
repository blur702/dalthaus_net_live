<?php

declare(strict_types=1);

namespace CMS\Models;

use CMS\Utils\Database;

/**
 * Base Model Class
 * 
 * Provides common database operations and utilities for all models.
 * Implements Active Record pattern with database abstraction.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
abstract class BaseModel
{
    /**
     * Database instance
     */
    protected Database $db;

    /**
     * Table name
     */
    protected string $table = '';

    /**
     * Primary key column
     */
    protected string $primaryKey = 'id';

    /**
     * Model data
     */
    protected array $data = [];

    /**
     * Original data (for change detection)
     */
    protected array $original = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->db = Database::getInstance($config['database']);
    }

    /**
     * Find record by ID
     * 
     * @param int $id Record ID
     * @return static|null
     */
    public static function find(int $id): ?static
    {
        $instance = new static();
        $data = $instance->db->fetchRow(
            "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ?",
            [$id]
        );

        if ($data === false) {
            return null;
        }

        $instance->data = $data;
        $instance->original = $data;
        
        return $instance;
    }

    /**
     * Find all records
     * 
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @param int|null $limit LIMIT
     * @param int|null $offset OFFSET
     * @return array
     */
    public static function all(
        array $conditions = [],
        string $orderBy = '',
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $instance = new static();
        
        $query = "SELECT * FROM {$instance->table}";
        $params = [];

        // Add WHERE clause
        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereParts);
        }

        // Add ORDER BY clause
        if (!empty($orderBy)) {
            $query .= " ORDER BY {$orderBy}";
        }

        // Add LIMIT clause
        if ($limit !== null) {
            $query .= " LIMIT {$limit}";
            
            if ($offset !== null) {
                $query .= " OFFSET {$offset}";
            }
        }

        $results = $instance->db->fetchAll($query, $params);
        
        return array_map(function ($data) use ($instance) {
            $model = new static();
            $model->data = $data;
            $model->original = $data;
            return $model;
        }, $results);
    }

    /**
     * Find records with custom query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return array
     */
    public static function query(string $query, array $params = []): array
    {
        $instance = new static();
        $results = $instance->db->fetchAll($query, $params);
        
        return array_map(function ($data) use ($instance) {
            $model = new static();
            $model->data = $data;
            $model->original = $data;
            return $model;
        }, $results);
    }

    /**
     * Find single record with custom query
     * 
     * @param string $query SQL query
     * @param array $params Query parameters
     * @return static|null
     */
    public static function queryFirst(string $query, array $params = []): ?static
    {
        $instance = new static();
        $data = $instance->db->fetchRow($query, $params);

        if ($data === false) {
            return null;
        }

        $instance->data = $data;
        $instance->original = $data;
        
        return $instance;
    }

    /**
     * Count records
     * 
     * @param array $conditions WHERE conditions
     * @return int
     */
    public static function count(array $conditions = []): int
    {
        $instance = new static();
        
        $query = "SELECT COUNT(*) FROM {$instance->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereParts);
        }

        return (int) $instance->db->fetchColumn($query, $params);
    }

    /**
     * Create new record
     * 
     * @param array $data Record data
     * @return static
     */
    public static function create(array $data): static
    {
        $instance = new static();
        $id = $instance->db->insert($instance->table, $data);
        
        $data[$instance->primaryKey] = (int) $id;
        $instance->data = $data;
        $instance->original = $data;
        
        return $instance;
    }

    /**
     * Create new record and return ID
     * 
     * @param array $data Record data
     * @return string|null
     */
    public function createRecord(array $data): ?string
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Save current record
     * 
     * @return bool
     */
    public function save(): bool
    {
        if ($this->exists()) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Update current record
     * 
     * @return bool
     */
    protected function update(): bool
    {
        $id = $this->data[$this->primaryKey];
        $updateData = $this->getChangedData();
        
        if (empty($updateData)) {
            return true; // No changes to save
        }

        $updated = $this->db->update(
            $this->table,
            $updateData,
            "{$this->primaryKey} = ?",
            [$id]
        );

        if ($updated > 0) {
            $this->original = $this->data;
            return true;
        }

        return false;
    }

    /**
     * Insert new record
     * 
     * @return bool
     */
    protected function insert(): bool
    {
        $insertData = $this->data;
        unset($insertData[$this->primaryKey]); // Remove primary key for insert
        
        $id = $this->db->insert($this->table, $insertData);
        
        if ($id) {
            $this->data[$this->primaryKey] = (int) $id;
            $this->original = $this->data;
            return true;
        }

        return false;
    }

    /**
     * Delete current record
     * 
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $id = $this->data[$this->primaryKey];
        $deleted = $this->db->delete(
            $this->table,
            "{$this->primaryKey} = ?",
            [$id]
        );

        return $deleted > 0;
    }

    /**
     * Check if record exists
     * 
     * @return bool
     */
    public function exists(): bool
    {
        return isset($this->data[$this->primaryKey]) && $this->data[$this->primaryKey] > 0;
    }

    /**
     * Get changed data
     * 
     * @return array
     */
    protected function getChangedData(): array
    {
        $changed = [];
        
        foreach ($this->data as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    /**
     * Get attribute value
     * 
     * @param string $key Attribute name
     * @return mixed
     */
    public function getAttribute(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
    
    /**
     * Get the ID of this model
     * 
     * @return int|null
     */
    public function getId(): ?int
    {
        $id = $this->getAttribute($this->primaryKey);
        return $id !== null ? (int) $id : null;
    }

    /**
     * Set attribute value
     * 
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->data;
    }

    /**
     * Set multiple attributes
     * 
     * @param array $attributes Attributes array
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Magic getter
     * 
     * @param string $key Attribute name
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     * 
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     * 
     * @param string $key Attribute name
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Magic unset
     * 
     * @param string $key Attribute name
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }
}
