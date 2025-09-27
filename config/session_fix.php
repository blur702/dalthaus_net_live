<?php
/**
 * Session Storage Fix for Production Server
 *
 * This script implements database-based session storage to work around
 * server-side session file storage issues.
 */

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function open($save_path, $session_name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($session_id): string
    {
        try {
            $stmt = $this->db->prepare('SELECT session_data FROM user_sessions WHERE session_id = ? AND expires > NOW()');
            $stmt->execute([$session_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['session_data'] : '';
        } catch (Exception $e) {
            error_log("Session read error: " . $e->getMessage());
            return '';
        }
    }

    public function write($session_id, $session_data): bool
    {
        try {
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours

            $stmt = $this->db->prepare(
                'INSERT INTO user_sessions (session_id, session_data, expires)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE session_data = ?, expires = ?'
            );

            return $stmt->execute([$session_id, $session_data, $expires, $session_data, $expires]);
        } catch (Exception $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy($session_id): bool
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM user_sessions WHERE session_id = ?');
            return $stmt->execute([$session_id]);
        } catch (Exception $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }

    public function gc($maxlifetime): int|false
    {
        try {
            $stmt = $this->db->prepare('DELETE FROM user_sessions WHERE expires < NOW()');
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Session GC error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * SQL to create the sessions table:
 *
 * CREATE TABLE user_sessions (
 *     session_id VARCHAR(128) PRIMARY KEY,
 *     session_data TEXT,
 *     expires DATETIME,
 *     INDEX idx_expires (expires)
 * );
 */
?>