#!/usr/bin/env python3
"""
Update user display_name to show proper name on frontend
"""

import paramiko
from ssh_config import SSH_CONFIG

def main():
    print("Updating user display_name for better frontend display...")
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
        
        # Run SQL commands directly
        print("\nChecking current user data...")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php -r "'
            '$config = require \"config/config.php\"; '
            '$dsn = \"mysql:host={$config[\"database\"][\"host\"]};dbname={$config[\"database\"][\"dbname\"]};charset={$config[\"database\"][\"charset\"]}\"; '
            '$db = new PDO($dsn, $config[\"database\"][\"username\"], $config[\"database\"][\"password\"]); '
            '$stmt = $db->prepare(\"SELECT user_id, username, display_name FROM users WHERE username = ?\"); '
            '$stmt->execute([\"kevin\"]); '
            '$user = $stmt->fetch(PDO::FETCH_ASSOC); '
            'if ($user) { '
            '    echo \"Current User - ID: {$user[\"user_id\"]}, Username: {$user[\"username\"]}, Display Name: {$user[\"display_name\"]}\\n\"; '
            '} else { '
            '    echo \"User kevin not found\\n\"; '
            '}"'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        print("Updating display_name to Kevin Althaus...")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php -r "'
            '$config = require \"config/config.php\"; '
            '$dsn = \"mysql:host={$config[\"database\"][\"host\"]};dbname={$config[\"database\"][\"dbname\"]};charset={$config[\"database\"][\"charset\"]}\"; '
            '$db = new PDO($dsn, $config[\"database\"][\"username\"], $config[\"database\"][\"password\"]); '
            '$stmt = $db->prepare(\"UPDATE users SET display_name = ? WHERE username = ?\"); '
            '$result = $stmt->execute([\"Kevin Althaus\", \"kevin\"]); '
            'if ($result) { '
            '    echo \"Successfully updated display_name to Kevin Althaus\\n\"; '
            '} else { '
            '    echo \"Failed to update display_name\\n\"; '
            '}"'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        print("Verifying update...")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php -r "'
            '$config = require \"config/config.php\"; '
            '$dsn = \"mysql:host={$config[\"database\"][\"host\"]};dbname={$config[\"database\"][\"dbname\"]};charset={$config[\"database\"][\"charset\"]}\"; '
            '$db = new PDO($dsn, $config[\"database\"][\"username\"], $config[\"database\"][\"password\"]); '
            '$stmt = $db->prepare(\"SELECT user_id, username, display_name FROM users WHERE username = ?\"); '
            '$stmt->execute([\"kevin\"]); '
            '$user = $stmt->fetch(PDO::FETCH_ASSOC); '
            'if ($user) { '
            '    echo \"Updated User - ID: {$user[\"user_id\"]}, Username: {$user[\"username\"]}, Display Name: {$user[\"display_name\"]}\\n\"; '
            '} else { '
            '    echo \"User kevin not found\\n\"; '
            '}"'
        )
        
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
        
        print("\n" + "=" * 50)
        print("Display name update complete!")
        
    except Exception as e:
        print(f"Error: {str(e)}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()