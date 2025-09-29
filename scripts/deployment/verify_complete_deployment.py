#!/usr/bin/env python3
"""
Verify complete deployment status: git commits and database state
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    print("VERIFYING COMPLETE DEPLOYMENT STATUS")
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
        
        # Check git commits on production
        print("\n🔄 GIT STATUS ON PRODUCTION:")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && git log --oneline -5'
        )
        output = stdout.read().decode()
        if output:
            print(output.strip())
        
        # Check if production is up to date with remote
        print("\n📡 CHECKING IF PRODUCTION IS UP TO DATE:")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && git fetch && git status'
        )
        output = stdout.read().decode()
        if output:
            print(output.strip())
            
        # Check database migration status
        print("\n🗄️ DATABASE MIGRATION STATUS:")
        check_script = '''<?php
$config = require "config/config.php";
$dsn = "mysql:host={$config["database"]["host"]};dbname={$config["database"]["dbname"]};charset={$config["database"]["charset"]}";
$db = new PDO($dsn, $config["database"]["username"], $config["database"]["password"]);

// Check if display_name column exists
$stmt = $db->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
$displayNameExists = false;
foreach ($columns as $col) {
    if ($col["Field"] === "display_name") {
        $displayNameExists = true;
        echo "✓ display_name field exists: {$col["Type"]}\\n";
        break;
    }
}
if (!$displayNameExists) {
    echo "✗ display_name field NOT found\\n";
}

// Check if users have display_name values
$stmt = $db->query("SELECT username, display_name FROM users WHERE display_name IS NOT NULL AND display_name != ''");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\\n✓ Users with display_name set: " . count($users) . "\\n";
foreach ($users as $user) {
    echo "  - {$user["username"]} → {$user["display_name"]}\\n";
}
?>'''
        
        # Write and run the check script
        stdin, stdout, stderr = ssh.exec_command(
            f'cd /home/dalthaus/public_html && echo \'{check_script}\' > deployment_check.php'
        )
        stdout.read()
        
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php deployment_check.php'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        # Clean up
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && rm -f deployment_check.php'
        )
        
        print("\n" + "=" * 50)
        print("✅ DEPLOYMENT VERIFICATION COMPLETE")
        
    except Exception as e:
        print(f"❌ Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()