#!/usr/bin/env python3
"""Create an index.html that redirects to index.php"""

import os
import sys
import paramiko
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

def create_redirect_index():
    """Create index.html that redirects to index.php"""
    
    # Get SSH credentials from .env
    host = 'mi3-cl9-its2.a2hosting.com'
    port = 7822
    username = os.getenv('SSH_USER', 'dalthaus')
    password = os.getenv('SSH_PASS')
    web_root = os.getenv('WEB_ROOT', '/home/dalthaus/public_html')
    
    if not password:
        print("[ERROR] SSH_PASS not found in .env file!")
        return False
    
    redirect_html = '''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=index.php">
    <script type="text/javascript">
        window.location.href = "index.php";
    </script>
    <title>Redirecting...</title>
</head>
<body>
    <p>If you are not redirected automatically, <a href="index.php">click here</a>.</p>
</body>
</html>'''
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        print(f"Connecting to {username}@{host}:{port}...")
        ssh.connect(host, port=port, username=username, password=password)
        print("[SUCCESS] Connected to server!")
        
        # Create the redirect index.html
        print("\n--- Creating redirect index.html ---")
        cmd = f"cat > {web_root}/index.html << 'EOF'\n{redirect_html}\nEOF"
        stdin, stdout, stderr = ssh.exec_command(cmd)
        output = stdout.read().decode('utf-8').strip()
        error = stderr.read().decode('utf-8').strip()
        
        if error:
            print(f"[ERROR] {error}")
        else:
            print("[SUCCESS] Redirect index.html created!")
        
        # Verify the file was created
        print("\n--- Verifying index.html ---")
        stdin, stdout, stderr = ssh.exec_command(f"ls -la {web_root}/index.html && head -5 {web_root}/index.html")
        output = stdout.read().decode('utf-8').strip()
        print(output)
        
        ssh.close()
        return True
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return False

if __name__ == "__main__":
    create_redirect_index()