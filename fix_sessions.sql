-- Database migration to add session storage table
-- This fixes the authentication issues by providing reliable session storage

CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    session_data TEXT,
    expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expires (expires),
    INDEX idx_created (created_at)
);

-- Clean up any existing expired sessions
DELETE FROM user_sessions WHERE expires < NOW();