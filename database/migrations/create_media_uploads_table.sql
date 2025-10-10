-- Media Uploads Tracking Table
-- Tracks all images uploaded through TinyMCE editor and dual image uploader

CREATE TABLE IF NOT EXISTS media_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    file_type VARCHAR(50),
    upload_type ENUM('tinymce', 'dual_display', 'dual_modal', 'featured', 'teaser') DEFAULT 'tinymce',
    user_id INT,
    content_id INT NULL,
    used_in_content BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_filename (filename),
    INDEX idx_user_id (user_id),
    INDEX idx_content_id (content_id),
    INDEX idx_upload_type (upload_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (content_id) REFERENCES content(content_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
