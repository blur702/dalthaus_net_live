#!/usr/bin/env python3
"""Manual deployment script for view files."""

import paramiko
import os
from pathlib import Path

# Configuration
SERVER_HOST = 'mi3-cl9-its2.a2hosting.com'
SERVER_PORT = 7822
SERVER_USER = 'dalthaus'
SERVER_PASSWORD = 'A2139@Pimaq'
REMOTE_BASE = '/home/dalthaus/public_html'

def upload_file(sftp, local_path, remote_path):
    """Upload a single file to the server."""
    print(f"Uploading {local_path} to {remote_path}...")
    sftp.put(local_path, remote_path)
    print(f"File uploaded successfully!")

def main():
    # Connect to server
    print("Connecting to server...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER_HOST, port=SERVER_PORT, username=SERVER_USER, password=SERVER_PASSWORD)
    sftp = ssh.open_sftp()

    # Files to upload - note the mixed case in the paths
    files_to_upload = [
        ('src\\Views\\Admin\\content\\index.php', 'src/Views/Admin/content/index.php'),
        ('src\\Views\\Layouts\\admin.php', 'src/Views/Layouts/admin.php'),
        ('src\\Views\\Layouts\\default.php', 'src/Views/Layouts/default.php'),
        ('src\\Views\\Public\\home\\index.php', 'src/Views/Public/home/index.php'),
        ('src\\Views\\Public\\photobooks\\index.php', 'src/Views/Public/photobooks/index.php')
    ]

    for local, remote in files_to_upload:
        local_path = Path(local)
        remote_path = f"{REMOTE_BASE}/{remote}"

        if local_path.exists():
            # Create backup
            stdin, stdout, stderr = ssh.exec_command(f"cp {remote_path} {remote_path}.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || true")
            stdout.read()

            # Upload file
            upload_file(sftp, str(local_path), remote_path)
        else:
            print(f"Warning: Local file {local_path} not found, skipping...")

    # Close connections
    sftp.close()
    ssh.close()
    print("\nManual deployment of view files completed successfully!")

if __name__ == "__main__":
    main()