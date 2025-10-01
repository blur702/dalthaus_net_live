#!/usr/bin/env python3
"""Add headers to .htaccess to disable Cloudflare caching during development"""

import os
import sys
import paramiko
from dotenv import load_dotenv
from datetime import datetime

# Load environment variables
load_dotenv()

def disable_cache():
    """Add cache bypass headers to .htaccess"""
    
    # Get SSH credentials from .env
    host = 'mi3-cl9-its2.a2hosting.com'
    port = 7822
    username = os.getenv('SSH_USER', 'dalthaus')
    password = os.getenv('SSH_PASS')
    web_root = os.getenv('WEB_ROOT', '/home/dalthaus/public_html')
    
    if not password:
        print("[ERROR] SSH_PASS not found in .env file!")
        return False
    
    # Cache bypass headers to add
    cache_bypass_config = """
# ===== DEVELOPMENT MODE - DISABLE CACHING =====
# Added: {}
# Remove this section when development is complete
<IfModule mod_headers.c>
    # Disable all caching
    Header set Cache-Control "no-cache, no-store, must-revalidate, private"
    Header set Pragma "no-cache"
    Header set Expires "0"
    
    # Tell Cloudflare to bypass cache
    Header set CDN-Cache-Control "no-cache"
    Header set Cloudflare-CDN-Cache-Control "no-cache"
    
    # Additional headers to prevent caching
    Header set X-Development-Mode "active"
</IfModule>

# Force PHP to not cache
<FilesMatch "\.(php|html?)$">
    <IfModule mod_headers.c>
        Header set Cache-Control "private, no-cache, no-store, must-revalidate"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </IfModule>
</FilesMatch>
# ===== END DEVELOPMENT MODE =====

""".format(datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        print(f"Connecting to {username}@{host}:{port}...")
        ssh.connect(host, port=port, username=username, password=password)
        print("[SUCCESS] Connected to server!")
        
        # Backup current .htaccess
        backup_name = f".htaccess.backup.{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        print(f"\n--- Creating backup: {backup_name} ---")
        stdin, stdout, stderr = ssh.exec_command(f"cd {web_root} && cp .htaccess {backup_name}")
        error = stderr.read().decode('utf-8').strip()
        if error:
            print(f"[ERROR] {error}")
        else:
            print(f"[SUCCESS] Backup created: {backup_name}")
        
        # Read current .htaccess
        print("\n--- Reading current .htaccess ---")
        stdin, stdout, stderr = ssh.exec_command(f"cat {web_root}/.htaccess")
        current_htaccess = stdout.read().decode('utf-8')
        
        # Check if development mode is already enabled
        if "DEVELOPMENT MODE" in current_htaccess:
            print("[INFO] Development mode headers already present in .htaccess")
            print("Would you like to remove them instead? (This would re-enable caching)")
            return False
        
        # Add cache bypass headers after the RewriteEngine On line
        lines = current_htaccess.split('\n')
        new_lines = []
        added = False
        
        for line in lines:
            new_lines.append(line)
            if 'RewriteEngine On' in line and not added:
                new_lines.append(cache_bypass_config)
                added = True
        
        if not added:
            # If RewriteEngine On not found, add at the beginning
            new_lines = [cache_bypass_config] + new_lines
        
        new_htaccess = '\n'.join(new_lines)
        
        # Write updated .htaccess
        print("\n--- Updating .htaccess with cache bypass headers ---")
        # Use a temporary file approach
        temp_file = f"/tmp/htaccess_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        # Create temp file locally first
        with open('/tmp/temp_htaccess', 'w') as f:
            f.write(new_htaccess)
        
        # Upload via SFTP
        sftp = ssh.open_sftp()
        sftp.put('/tmp/temp_htaccess', f"{web_root}/.htaccess")
        sftp.close()
        
        print("[SUCCESS] .htaccess updated with cache bypass headers!")
        
        # Verify the changes
        print("\n--- Verifying changes ---")
        stdin, stdout, stderr = ssh.exec_command(f"grep -A 5 'DEVELOPMENT MODE' {web_root}/.htaccess")
        output = stdout.read().decode('utf-8').strip()
        if output:
            print(output)
        
        # Test with curl to verify headers
        print("\n--- Testing cache headers ---")
        stdin, stdout, stderr = ssh.exec_command(f"curl -I http://localhost/ 2>/dev/null | grep -i cache")
        output = stdout.read().decode('utf-8').strip()
        if output:
            print("Cache headers from local test:")
            print(output)
        
        ssh.close()
        
        print("\n" + "="*50)
        print("SUCCESS! Cloudflare caching has been disabled.")
        print("="*50)
        print("\nWhat this does:")
        print("1. Sets Cache-Control headers to prevent caching")
        print("2. Tells Cloudflare to bypass cache")
        print("3. Forces revalidation on every request")
        print("\nNOTE: You should also:")
        print("1. Enable 'Development Mode' in Cloudflare dashboard")
        print("   (Settings > Caching > Configuration > Development Mode)")
        print("2. Or create a Page Rule: *dalthaus.net/* -> Cache Level: Bypass")
        print("\nTo re-enable caching later, run the remove_dev_headers.py script")
        
        return True
        
    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return False

if __name__ == "__main__":
    disable_cache()