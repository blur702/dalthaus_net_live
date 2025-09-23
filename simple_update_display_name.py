#!/usr/bin/env python3
"""
Simple script to update user display_name
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    print("Updating user display_name to Kevin Althaus...")
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
        
        # Create a simple PHP script
        php_script = '''<?php
$config = require "config/config.php";
$dsn = "mysql:host=" . $config["database"]["host"] . ";dbname=" . $config["database"]["dbname"] . ";charset=" . $config["database"]["charset"];
$db = new PDO($dsn, $config["database"]["username"], $config["database"]["password"]);

echo "Current user data:\\n";
$stmt = $db->prepare("SELECT user_id, username, display_name FROM users WHERE username = ?");
$stmt->execute(["kevin"]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "ID: " . $user["user_id"] . ", Username: " . $user["username"] . ", Display Name: " . $user["display_name"] . "\\n";
} else {
    echo "User not found\\n";
    exit(1);
}

echo "\\nUpdating display_name to Kevin Althaus...\\n";
$stmt = $db->prepare("UPDATE users SET display_name = ? WHERE username = ?");
$result = $stmt->execute(["Kevin Althaus", "kevin"]);

if ($result) {
    echo "Successfully updated!\\n";
    
    // Verify
    $stmt = $db->prepare("SELECT user_id, username, display_name FROM users WHERE username = ?");
    $stmt->execute(["kevin"]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New data: ID: " . $user["user_id"] . ", Username: " . $user["username"] . ", Display Name: " . $user["display_name"] . "\\n";
} else {
    echo "Update failed!\\n";
}
?>'''
        
        # Write the PHP script to the server
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && cat > update_display.php << \'EOF\'\n' + php_script + '\nEOF'
        )
        stdout.read()
        
        # Run the script
        print("Running update script...")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php update_display.php'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        # Clean up
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && rm -f update_display.php'
        )
        
        print("=" * 50)
        print("Update complete!")
        
    except Exception as e:
        print(f"Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()