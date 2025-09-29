#!/usr/bin/env python3
"""
Manual deployment script to upload the Auth.php utility file
"""

import paramiko
import os
from ssh_config import SSH_CONFIG

def upload_auth_util_file():
    """Upload the Auth.php utility file directly"""

    # Local file path
    local_file = r"src\Utils\Auth.php"
    remote_file = "/home/dalthaus/public_html/src/Utils/Auth.php"

    if not os.path.exists(local_file):
        print(f"ERROR: Local file {local_file} not found!")
        return False

    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

        print("Connecting to server...")
        ssh.connect(
            hostname=SSH_CONFIG['host'],
            port=SSH_CONFIG['port'],
            username=SSH_CONFIG['username'],
            password=SSH_CONFIG['password']
        )

        # Create SFTP client
        sftp = ssh.open_sftp()

        # Backup existing file first
        print("Creating backup of existing Auth.php util...")
        backup_cmd = f"cp {remote_file} {remote_file}.backup.$(date +%Y%m%d_%H%M%S)"
        stdin, stdout, stderr = ssh.exec_command(backup_cmd)
        exit_status = stdout.channel.recv_exit_status()

        if exit_status == 0:
            print("Backup created successfully")
        else:
            print("Warning: Backup failed, but continuing...")

        # Upload the file
        print(f"Uploading {local_file} to {remote_file}...")
        sftp.put(local_file, remote_file)
        print("File uploaded successfully!")

        # Verify the upload
        print("Verifying upload...")
        stdin, stdout, stderr = ssh.exec_command(f"ls -la {remote_file}")
        output = stdout.read().decode()
        print(f"Remote file info: {output.strip()}")

        # Check the specific line we changed
        print("Checking the user_id cast line...")
        stdin, stdout, stderr = ssh.exec_command(f"grep -n 'storeRememberToken.*int.*user_id' {remote_file}")
        output = stdout.read().decode()
        if output:
            print(f"Found cast line: {output.strip()}")
        else:
            print("Could not find user_id cast line in uploaded file")

        sftp.close()
        ssh.close()

        print("Manual deployment of Auth util completed successfully!")
        return True

    except Exception as e:
        print(f"ERROR: {e}")
        return False

if __name__ == "__main__":
    upload_auth_util_file()