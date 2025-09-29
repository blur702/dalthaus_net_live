#!/usr/bin/env python3
"""Deploy a single file to the production server."""

import paramiko
import sys
from pathlib import Path

# Configuration
SERVER_HOST = 'mi3-cl9-its2.a2hosting.com'
SERVER_PORT = 7822
SERVER_USER = 'dalthaus'
SERVER_PASSWORD = 'A2139@Pimaq'
REMOTE_BASE = '/home/dalthaus/public_html'

def main():
    # Connect to server
    print("Connecting to server...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(SERVER_HOST, port=SERVER_PORT, username=SERVER_USER, password=SERVER_PASSWORD)
    sftp = ssh.open_sftp()

    # File to upload
    local_file = 'src\\Views\\Admin\\content\\index.php'
    remote_file = 'src/Views/Admin/content/index.php'

    local_path = Path(local_file)
    remote_path = f"{REMOTE_BASE}/{remote_file}"

    if local_path.exists():
        # Create backup
        print(f"Creating backup of {remote_file}...")
        stdin, stdout, stderr = ssh.exec_command(f"cp {remote_path} {remote_path}.backup.$(date +%Y%m%d_%H%M%S) 2>/dev/null || true")
        stdout.read()

        # Upload file
        print(f"Uploading {local_file}...")
        sftp.put(str(local_path), remote_path)
        print(f"File uploaded successfully!")

        # Verify
        stdin, stdout, stderr = ssh.exec_command(f"ls -la {remote_path}")
        print(f"Verification: {stdout.read().decode().strip()}")
    else:
        print(f"Error: Local file {local_path} not found!")
        sys.exit(1)

    # Close connections
    sftp.close()
    ssh.close()
    print("\nDeployment completed successfully!")

if __name__ == "__main__":
    main()