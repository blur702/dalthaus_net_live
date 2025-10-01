#!/usr/bin/env python3
"""Remove development cache bypass headers from .htaccess"""

import os
import sys
import paramiko
from dotenv import load_dotenv
from datetime import datetime

# Load environment variables
load_dotenv()

def remove_dev_headers():
    """Remove development mode headers from .htaccess"""
    
    # Get SSH credentials from .env
    host = 'mi3-cl9-its2.a2hosting.com'
    port = 7822
    username = os.getenv('SSH_USER', 'dalthaus')
    password = os.getenv('SSH_PASS')
    web_root = os.getenv('WEB_ROOT', '/home/dalthaus/public_html')
    
    if not password:
        print("[ERROR] SSH_PASS not found in .env file!")
        return False
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        print(f"Connecting to {username}@{host}:{port}...")
        ssh.connect(host, port=port, username=username, password=password)
        print("[SUCCESS] Connected to server!")
        
        # Read current .htaccess
        print("\n--- Reading current .htaccess ---")
        stdin, stdout, stderr = ssh.exec_command(f"cat {web_root}/.htaccess")
        current_htaccess = stdout.read().decode('utf-8')
        
        # Check if development mode headers are present
        if "DEVELOPMENT MODE" not in current_htaccess:
            print("[INFO] No development mode headers found in .htaccess")
            print("Caching is already enabled.")
            return True
        
        # Backup current .htaccess
        backup_name = f".htaccess.backup.before_reenable_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        print(f"\n--- Creating backup: {backup_name} ---")
        stdin, stdout, stderr = ssh.exec_command(f"cd {web_root} && cp .htaccess {backup_name}")
        error = stderr.read().decode('utf-8').strip()
        if error:
            print(f"[ERROR] {error}")
        else:
            print(f"[SUCCESS] Backup created: {backup_name}")
        
        # Remove development mode section
        print("\n--- Removing development mode headers ---")
        lines = current_htaccess.split('\n')
        new_lines = []
        skip = False
        
        for line in lines:
            if "===== DEVELOPMENT MODE" in line:
                skip = True
                continue
            elif "===== END DEVELOPMENT MODE" in line:
                skip = False
                continue
            elif not skip:
                new_lines.append(line)
        
        # Clean up any extra blank lines
        while new_lines and not new_lines[-1].strip():
            new_lines.pop()
        while new_lines and not new_lines[0].strip():
            new_lines.pop(0)
        
        new_htaccess = '\n'.join(new_lines)
        
        # Write updated .htaccess
        print("\n--- Updating .htaccess ---")
        with open('/tmp/temp_htaccess', 'w') as f:
            f.write(new_htaccess)
        
        # Upload via SFTP
        sftp = ssh.open_sftp()
        sftp.put('/tmp/temp_htaccess', f"{web_root}/.htaccess")
        sftp.close()
        
        print("[SUCCESS] Development headers removed from .htaccess!")
        
        # Verify the changes
        print("\n--- Verifying removal ---")
        stdin, stdout, stderr = ssh.exec_command(f"grep -c 'DEVELOPMENT MODE' {web_root}/.htaccess || echo '0'")
        output = stdout.read().decode('utf-8').strip()
        if output == "0":
            print("[SUCCESS] Development mode headers completely removed")
        else:
            print(f"[WARNING] Found {output} references to DEVELOPMENT MODE still in file")
        
        ssh.close()
        
        print("\n" + "="*50)
        print("SUCCESS! Caching has been re-enabled.")
        print("="*50)
        print("\nCloudflare will now cache your pages normally.")
        print("Remember to also disable Development Mode in Cloudflare dashboard if you enabled it.")
        
        return True
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return False

if __name__ == "__main__":
    remove_dev_headers()