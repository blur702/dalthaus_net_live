#!/usr/bin/env python3
"""
Simple check for existing content
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        ssh.connect(
            hostname=SSH_CONFIG['host'],
            port=SSH_CONFIG['port'],
            username=SSH_CONFIG['username'],
            password=SSH_CONFIG['password']
        )
        
        # Simple script to check content
        check_script = '''<?php
$config = require "config/config.php";
$dsn = "mysql:host={$config["database"]["host"]};dbname={$config["database"]["dbname"]};charset={$config["database"]["charset"]}";
$db = new PDO($dsn, $config["database"]["username"], $config["database"]["password"]);

echo "Checking content...\\n";

$stmt = $db->prepare("SELECT id, title, content_type, body FROM content WHERE body LIKE '%img%' LIMIT 5");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    echo "Found content with images:\\n";
    foreach ($results as $content) {
        echo "ID: " . $content['id'] . " - " . $content['title'] . "\\n";
        if (strpos($content['body'], '<img') !== false) {
            echo "  Contains HTML img tags\\n";
        }
    }
} else {
    echo "No content with images found.\\n";
}
?>'''
        
        stdin, stdout, stderr = ssh.exec_command(
            f'cd /home/dalthaus/public_html && cat > simple_check.php << \'EOF\'\n{check_script}\nEOF'
        )
        stdout.read()
        
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php simple_check.php'
        )
        
        output = stdout.read().decode()
        if output:
            print(output)
            
        # Clean up
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && rm -f simple_check.php'
        )
        
    except Exception as e:
        print(f"Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()