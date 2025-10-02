#!/usr/bin/env python3
"""Restore index.html from backup temporarily to fix 404 issue"""

import os
import sys
import paramiko
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def restore_index():
    """Restore index.html from backup"""
    
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
        
        # Commands to execute
        commands = [
            ("Check for backups", f"ls -la {web_root}/index.html.backup* 2>/dev/null || echo 'No backups found'"),
            ("Copy backup to index.html", f"cd {web_root} && cp index.html.backup.20251001_191217 index.html"),
            ("Verify index.html exists", f"ls -la {web_root}/index.html"),
            ("Check both index files", f"ls -la {web_root}/index.*"),
        ]
        
        for desc, cmd in commands:
            print(f"\n--- {desc} ---")
            stdin, stdout, stderr = ssh.exec_command(cmd)
            output = stdout.read().decode('utf-8').strip()
            error = stderr.read().decode('utf-8').strip()
            
            if output:
                print(output)
            if error:
                print(f"[ERROR] {error}")
        
        ssh.close()
        print("\n[SUCCESS] index.html has been restored from backup!")
        return True
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return False

if __name__ == "__main__":
    restore_index()