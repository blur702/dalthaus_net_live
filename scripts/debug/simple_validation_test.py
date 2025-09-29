#!/usr/bin/env python3
"""
Simple test to check user validation
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    print("Testing user validation on production...")
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
        
        # Create a simple validation test
        php_script = '''<?php
require_once "config/config.php";
require_once "src/Models/User.php";

use CMS\Models\User;

echo "Testing user validation for display name editing...\\n\\n";

// Simulate editing user dalthaus (ID 5)
$data = [
    "username" => "dalthaus",
    "display_name" => "Updated Display Name Test",
    "email" => "dalthaus@outlook.com",
    "password" => ""
];

echo "Testing data:\\n";
echo "Username: " . $data["username"] . "\\n";
echo "Display Name: " . $data["display_name"] . "\\n";
echo "Email: " . $data["email"] . "\\n";
echo "Password: [empty - no change]\\n\\n";

$errors = User::validateUserData($data, 5);

if (empty($errors)) {
    echo "SUCCESS: No validation errors\\n";
} else {
    echo "VALIDATION ERRORS FOUND:\\n";
    foreach ($errors as $field => $error) {
        echo "- $field: $error\\n";
    }
}
?>'''
        
        # Write and run the test
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && cat > validation_test.php << \'EOF\'\n' + php_script + '\nEOF'
        )
        stdout.read()
        
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php validation_test.php'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"PHP Error: {error}")
            
        # Clean up
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && rm -f validation_test.php'
        )
        
        print("=" * 50)
        print("Test complete!")
        
    except Exception as e:
        print(f"Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()