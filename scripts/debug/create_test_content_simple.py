#!/usr/bin/env python3
"""
Create test content with a fake image path to test the editor fix
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    print("Creating test content with image for editor testing...")
    print("=" * 50)
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        # Connect to server
        print(f"Connecting to {SSH_CONFIG['host']}...")
        ssh.connect(
            hostname=SSH_CONFIG['host'],
            port=SSH_CONFIG['port'],
            username=SSH_CONFIG['username'],
            password=SSH_CONFIG['password']
        )
        
        # Create a PHP script to add test content with image
        test_script = '''<?php
$config = require "config/config.php";
$dsn = "mysql:host={$config["database"]["host"]};dbname={$config["database"]["dbname"]};charset={$config["database"]["charset"]}";
$db = new PDO($dsn, $config["database"]["username"], $config["database"]["password"]);

echo "Creating test content with image paths...\\n";

// Insert test article with image paths
$stmt = $db->prepare("INSERT INTO content (title, url_alias, body, teaser, content_type, status, user_id, featured_image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$result = $stmt->execute([
    "Test Article with Image Path",
    "test-article-image-path", 
    "This is test content with an image for testing the editor fix.",
    "Testing image display in editor",
    "article",
    "draft",
    1, // user kevin
    "content/featured/2025/09/test-image.jpg" // This path will test our fix
]);

if ($result) {
    $contentId = $db->lastInsertId();
    echo "Created test article with ID: $contentId\\n";
    echo "  Featured image path: content/featured/2025/09/test-image.jpg\\n";
} else {
    echo "Failed to create test article\\n";
}

// Insert test photobook with both featured and teaser images
$stmt = $db->prepare("INSERT INTO content (title, url_alias, body, teaser, content_type, status, user_id, featured_image, teaser_image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$result = $stmt->execute([
    "Test Photobook with Images",
    "test-photobook-images",
    "This is test photobook content with images for testing the editor fix.",
    "Testing image display in photobook editor",
    "photobook", 
    "draft",
    1, // user kevin
    "content/featured/2025/09/test-featured.jpg", // Featured image
    "content/teaser/2025/09/test-teaser.jpg"     // Teaser image
]);

if ($result) {
    $contentId = $db->lastInsertId();
    echo "Created test photobook with ID: $contentId\\n";
    echo "  Featured image path: content/featured/2025/09/test-featured.jpg\\n";
    echo "  Teaser image path: content/teaser/2025/09/test-teaser.jpg\\n";
} else {
    echo "Failed to create test photobook\\n";
}

echo "\\nTest content created successfully!\\n";
echo "You can now test the image editor fix by editing these items.\\n";
?>'''
        
        # Write and run the test script
        stdin, stdout, stderr = ssh.exec_command(
            f'cd /home/dalthaus/public_html && cat > create_test_content.php << \'EOF\'\n{test_script}\nEOF'
        )
        stdout.read()
        
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php create_test_content.php'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        # Clean up
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && rm -f create_test_content.php'
        )
        
        print("=" * 50)
        print("Test content creation complete!")
        
    except Exception as e:
        print(f"Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()