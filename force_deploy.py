#!/usr/bin/env python3
"""
Force deployment script to ensure production has latest auto-save features
"""
import os
import paramiko
import logging
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# SSH credentials
SSH_HOST = "mi3-cl9-its2.a2hosting.com"
SSH_PORT = 7822
SSH_USER = os.getenv('SSH_USER', 'dalthaus')
SSH_PASS = os.getenv('SSH_PASS')
WEB_ROOT = os.getenv('WEB_ROOT', '/home/dalthaus/public_html')

def execute_ssh_command(ssh, command):
    """Execute a command via SSH and return output"""
    try:
        stdin, stdout, stderr = ssh.exec_command(command)
        exit_status = stdout.channel.recv_exit_status()
        output = stdout.read().decode('utf-8').strip()
        error = stderr.read().decode('utf-8').strip()
        
        print(f"Command: {command}")
        print(f"Exit status: {exit_status}")
        if output:
            print(f"Output: {output}")
        if error:
            print(f"Error: {error}")
        print("-" * 50)
        
        return output, error, exit_status
    except Exception as e:
        print(f"Error executing command '{command}': {e}")
        return "", str(e), 1

def main():
    # Create SSH client
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    try:
        print("Connecting to production server...")
        ssh.connect(SSH_HOST, port=SSH_PORT, username=SSH_USER, password=SSH_PASS)
        print("Connected!")
        
        # Check current commit
        print("\n=== CHECKING CURRENT COMMIT ===")
        output, error, status = execute_ssh_command(ssh, f"cd {WEB_ROOT} && git log -1 --oneline")
        current_commit = output.split()[0] if output else "unknown"
        print(f"Current commit: {current_commit}")
        
        # Check remote commits available
        print("\n=== CHECKING REMOTE COMMITS ===")
        execute_ssh_command(ssh, f"cd {WEB_ROOT} && git fetch origin")
        output, error, status = execute_ssh_command(ssh, f"cd {WEB_ROOT} && git log origin/main -5 --oneline")
        
        # Force reset to origin/main
        print("\n=== FORCE RESET TO LATEST ===")
        execute_ssh_command(ssh, f"cd {WEB_ROOT} && git reset --hard origin/main")
        
        # Verify the reset worked
        print("\n=== VERIFYING RESET ===")
        output, error, status = execute_ssh_command(ssh, f"cd {WEB_ROOT} && git log -1 --oneline")
        new_commit = output.split()[0] if output else "unknown"
        print(f"New commit: {new_commit}")
        
        # Check if auto-save files exist
        print("\n=== CHECKING AUTO-SAVE FILES ===")
        execute_ssh_command(ssh, f"ls -la {WEB_ROOT}/assets/js/autosave.js")
        execute_ssh_command(ssh, f"ls -la {WEB_ROOT}/src/Views/Admin/content/drafts.php")
        
        # Verify routes include auto-save endpoints
        print("\n=== CHECKING ROUTES ===")
        output, error, status = execute_ssh_command(ssh, f"grep -n 'autosave\\|create-draft' {WEB_ROOT}/config/routes.php")
        
        print("\n=== FORCE DEPLOYMENT COMPLETE ===")
        
    except Exception as e:
        print(f"SSH connection failed: {e}")
    finally:
        ssh.close()

if __name__ == "__main__":
    main()
