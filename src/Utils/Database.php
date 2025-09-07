<?php

declare(strict_types=1);

namespace CMS\Utils;

use PDO;
use PDOStatement;
use PDOException;
use Exception;

/**
 * Database Wrapper Class
 * 
 * Provides a secure PDO wrapper with prepared statements and connection management.
 * Implements singleton pattern to ensure single database connection.
 * 
 * @package CMS\Utils
 * @author  Kevin
 * @version 1.0.0
 */
class Database
{
    /**
     * Singleton instance
     */
    private static ?Database $instance = null;

    /**
     * PDO connection instance
     */
    private ?PDO $connection = null;

    /**
     * Database configuration
     */
    private array $config;

    /**
     * Private constructor to prevent direct instantiation
     * 
     * @param array $config Database configuration
     */
    private function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @param array|null $config Database configuration (required on first call)
     * @return Database
     * @throws Exception When configuration is missing on first call
     */
    public static function getInstance(?array $config = null): Database
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception('Database configuration is required on first call');
            }
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * @throws PDOException When connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['dbname'],
                $this->config['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get PDO connection
     * 
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Execute a prepared statement
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement
     * @throws PDOException When query execution fails
     */
    public function query(string $query, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new PDOException('Query execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch all rows from query result
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @return array
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single row from query result
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @return array|false
     */
    public function fetchRow(string $query, array $params = []): array|false
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch single column value
     * 
     * @param string $query SQL query
     * @param array $params Parameters to bind
     * @return mixed
     */
    public function fetchColumn(string $query, array $params = []): mixed
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Insert record and return last insert ID
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string Last insert ID
     */
    public function insert(string $table, array $data): string
    {
        $columns = implode(',', array_keys($data));
        $placeholders = rtrim(str_repeat('?,', count($data)), ',');
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($query, array_values($data));
        
        return $this->connection->lastInsertId();
    }

    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause with placeholders
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        
        // Use positional parameters for SET clause
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        $setClause = implode(', ', $sets);
        
        // Add WHERE parameters
        foreach ($whereParams as $param) {
            $params[] = $param;
        }
        
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause with placeholders
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Count records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (optional)
     * @param array $params Parameters for WHERE clause
     * @return int
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $query = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($query, $params);
    }

    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return bool
     */
    public function exists(string $table, string $where, array $params = []): bool
    {
        return $this->count($table, $where, $params) > 0;
    }

    /**
     * Begin transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    /**
     * Execute transaction with callback
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Return value of callback
     * @throws Exception When transaction fails
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get last insert ID
     * 
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Escape string for use in queries (use prepared statements instead when possible)
     * 
     * @param string $string String to escape
     * @return string
     */
    public function quote(string $string): string
    {
        return $this->connection->quote($string);
    }

    /**
     * Prevent cloning
     */
    private function __clone(): void
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize Database instance');
    }

    /**
     * Close connection on destruction
     */
    public function __destruct()
    {
        $this->connection = null;
    }
}
