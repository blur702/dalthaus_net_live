#!/usr/bin/env python3
"""
Deploy and run display_name migration on production server
"""

import paramiko
import sys
from ssh_config import SSH_CONFIG

def main():
    print("[DEPLOY] STARTING DEPLOYMENT AND MIGRATION")
    print("=" * 50)
    
    # Create SSH client
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
        print("[SUCCESS] Connected to server successfully!")
        
        # Pull latest code
        print("\n--- Pulling latest code from GitHub ---")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && git pull origin main'
        )
        print(stdout.read().decode())
        error = stderr.read().decode()
        if error and 'Already up to date' not in error:
            print(f"Error: {error}")
        
        # Run the migration
        print("\n--- Running display_name migration ---")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php apply_display_name_migration_prod.php'
        )
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        # Verify the migration
        print("\n--- Verifying migration ---")
        stdin, stdout, stderr = ssh.exec_command(
            'cd /home/dalthaus/public_html && php verify_display_name.php'
        )
        output = stdout.read().decode()
        error = stderr.read().decode()
        
        if output:
            print(output)
        if error:
            print(f"Error: {error}")
            
        print("\n[SUCCESS] Deployment and migration completed!")
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        sys.exit(1)
    finally:
        ssh.close()
        print("Disconnected from server")

if __name__ == "__main__":
    main()