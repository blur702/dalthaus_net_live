#!/usr/bin/env python3
"""
Disable Maintenance Mode Script
Connects to production server and disables maintenance mode
"""

import sys
import os

# Add current directory to path so we can import ssh_agent
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent

class MaintenanceManager:
    """Manages maintenance mode on the production server"""
    
    def __init__(self):
        # Load SSH configuration
        try:
            from ssh_config import SSH_CONFIG
            self.host = SSH_CONFIG["host"]
            self.username = SSH_CONFIG["username"]
            self.password = SSH_CONFIG["password"]
            self.port = SSH_CONFIG["port"]
            self.web_root = SSH_CONFIG["web_root"]
        except ImportError:
            print("[ERROR] ssh_config.py not found!")
            print("Please copy ssh_config.template.py to ssh_config.py and configure your credentials")
            sys.exit(1)
        except KeyError as e:
            print(f"[ERROR] Missing configuration key: {e}")
            print("Please check your ssh_config.py file")
            sys.exit(1)
        
        self.agent = None
    
    def connect(self):
        """Connect to the server"""
        print(f"Connecting to {self.host}...")
        self.agent = SSHAgent(self.host, self.username, self.password, self.port)
        
        if self.agent.connect():
            print("[SUCCESS] Connected to server successfully!")
            return True
        else:
            print("[ERROR] Failed to connect to server")
            return False
    
    def disconnect(self):
        """Disconnect from server"""
        if self.agent:
            self.agent.disconnect()
            print("Disconnected from server")
    
    def check_maintenance_files(self):
        """Check for common maintenance mode files"""
        print("\n--- Checking for maintenance mode files ---")
        
        # Common maintenance file patterns
        maintenance_files = [
            f"{self.web_root}/maintenance.php",
            f"{self.web_root}/.maintenance",
            f"{self.web_root}/maintenance.html", 
            f"{self.web_root}/maintenance.txt",
            f"{self.web_root}/down.php",
            f"{self.web_root}/under_maintenance.php",
            f"{self.web_root}/503.php"
        ]
        
        found_files = []
        
        for file_path in maintenance_files:
            # Check if file exists
            exit_code, stdout, stderr = self.agent.execute_command(
                f"test -f '{file_path}' && echo 'exists' || echo 'not found'", 
                show_output=False
            )
            
            if 'exists' in stdout:
                found_files.append(file_path)
                print(f"[FOUND] {file_path}")
                
                # Get file details
                perms = self.agent.check_file_permissions(file_path)
                if perms:
                    print(f"  Permissions: {perms.get('permissions', 'unknown')}")
                    print(f"  Size: {perms.get('size', 'unknown')} bytes")
                    print(f"  Modified: {perms.get('modified', 'unknown')}")
            else:
                print(f"[NOT FOUND] {file_path}")
        
        return found_files
    
    def check_htaccess_maintenance(self):
        """Check for maintenance redirects in .htaccess"""
        print("\n--- Checking .htaccess for maintenance redirects ---")
        
        htaccess_path = f"{self.web_root}/.htaccess"
        
        # Check if .htaccess exists
        exit_code, stdout, stderr = self.agent.execute_command(
            f"test -f '{htaccess_path}' && echo 'exists' || echo 'not found'", 
            show_output=False
        )
        
        if 'exists' not in stdout:
            print("[NOT FOUND] .htaccess file")
            return False
        
        # Read .htaccess content
        success, content = self.agent.read_file(htaccess_path)
        if not success:
            print(f"[ERROR] Could not read .htaccess: {content}")
            return False
        
        print(f"[FOUND] .htaccess file exists")
        
        # Check for maintenance-related rules
        maintenance_keywords = [
            'maintenance',
            'under construction', 
            'site down',
            'coming soon',
            '503',
            'RewriteRule.*maintenance',
            'ErrorDocument 503'
        ]
        
        maintenance_rules = []
        lines = content.split('\n')
        
        for i, line in enumerate(lines, 1):
            line_lower = line.lower()
            for keyword in maintenance_keywords:
                if keyword in line_lower and not line.strip().startswith('#'):
                    maintenance_rules.append((i, line.strip()))
                    break
        
        if maintenance_rules:
            print(f"[FOUND] {len(maintenance_rules)} potential maintenance rules in .htaccess:")
            for line_num, rule in maintenance_rules:
                print(f"  Line {line_num}: {rule}")
            return True
        else:
            print("[OK] No maintenance rules found in .htaccess")
            return False
    
    def check_index_php_maintenance(self):
        """Check if index.php has maintenance mode logic"""
        print("\n--- Checking index.php for maintenance mode ---")
        
        index_path = f"{self.web_root}/index.php"
        
        # Check if index.php exists
        exit_code, stdout, stderr = self.agent.execute_command(
            f"test -f '{index_path}' && echo 'exists' || echo 'not found'", 
            show_output=False
        )
        
        if 'exists' not in stdout:
            print("[NOT FOUND] index.php file")
            return False
        
        # Read first part of index.php to check for maintenance logic
        success, content = self.agent.read_file(index_path)
        if not success:
            print(f"[ERROR] Could not read index.php: {content}")
            return False
        
        print(f"[FOUND] index.php file exists")
        
        # Check for maintenance-related code (first 50 lines should be enough)
        lines = content.split('\n')[:50]
        maintenance_found = False
        
        for i, line in enumerate(lines, 1):
            line_lower = line.lower()
            if any(keyword in line_lower for keyword in ['maintenance', 'under construction', 'site down', 'coming soon']):
                print(f"  Line {i}: {line.strip()}")
                maintenance_found = True
        
        if not maintenance_found:
            print("[OK] No obvious maintenance mode code in index.php")
        
        return maintenance_found
    
    def disable_maintenance_mode(self):
        """Disable maintenance mode by removing/renaming maintenance files"""
        print("\n--- Disabling maintenance mode ---")
        
        # First, check what maintenance files exist
        maintenance_files = self.check_maintenance_files()
        
        if not maintenance_files:
            print("[OK] No maintenance files found")
        
        disabled_files = []
        
        for file_path in maintenance_files:
            # Create backup and remove/rename the file
            backup_path = f"{file_path}.disabled.backup"
            
            print(f"Disabling: {file_path}")
            
            # Move file to backup location
            exit_code, stdout, stderr = self.agent.execute_command(
                f"mv '{file_path}' '{backup_path}'", 
                show_output=False
            )
            
            if exit_code == 0:
                print(f"[SUCCESS] Moved {file_path} to {backup_path}")
                disabled_files.append((file_path, backup_path))
            else:
                print(f"[ERROR] Failed to move {file_path}: {stderr}")
        
        # Check for .htaccess maintenance rules
        if self.check_htaccess_maintenance():
            print("\n[WARNING] .htaccess contains potential maintenance rules")
            print("You may need to manually edit .htaccess to remove maintenance redirects")
        
        return disabled_files
    
    def test_site_access(self):
        """Test if the site is accessible"""
        print("\n--- Testing site access ---")
        
        # Try to make a simple HTTP request to test accessibility
        # We'll use curl to test the site
        site_url = f"http://{self.host}"  # Assuming HTTP for now
        
        exit_code, stdout, stderr = self.agent.execute_command(
            f"curl -s -o /dev/null -w '%{{http_code}}' '{site_url}' --connect-timeout 10",
            show_output=False
        )
        
        if exit_code == 0 and stdout.strip():
            http_code = stdout.strip()
            print(f"[TEST] HTTP response code: {http_code}")
            
            if http_code == '200':
                print("[SUCCESS] Site is accessible!")
                return True
            elif http_code == '503':
                print("[WARNING] Site still returning 503 (Service Unavailable)")
                return False
            else:
                print(f"[INFO] Site returning HTTP {http_code}")
                return False
        else:
            print(f"[ERROR] Could not test site access: {stderr}")
            return False
    
    def run(self):
        """Main execution flow"""
        print("=== DISABLING MAINTENANCE MODE ===")
        print("=" * 50)
        
        if not self.connect():
            return False
        
        try:
            # 1. Check current maintenance files
            print("\n1. Checking current maintenance status...")
            maintenance_files = self.check_maintenance_files()
            self.check_htaccess_maintenance()
            self.check_index_php_maintenance()
            
            # 2. Disable maintenance mode
            print("\n2. Disabling maintenance mode...")
            disabled_files = self.disable_maintenance_mode()
            
            # 3. Test site access
            print("\n3. Testing site access...")
            site_accessible = self.test_site_access()
            
            # 4. Summary
            print("\n=== SUMMARY ===")
            if disabled_files:
                print(f"Disabled {len(disabled_files)} maintenance files:")
                for original, backup in disabled_files:
                    print(f"  {original} -> {backup}")
            else:
                print("No maintenance files were found to disable")
            
            if site_accessible:
                print("[SUCCESS] Site is now accessible!")
            else:
                print("[WARNING] Site may still be in maintenance mode")
                print("Check .htaccess rules or server configuration")
            
            return site_accessible
            
        except Exception as e:
            print(f"[ERROR] Error during maintenance mode disable: {e}")
            return False
        
        finally:
            self.disconnect()

def main():
    """Main CLI interface"""
    manager = MaintenanceManager()
    success = manager.run()
    
    if success:
        print("\n[COMPLETE] Maintenance mode has been disabled!")
    else:
        print("\n[INCOMPLETE] Could not fully disable maintenance mode")
        print("Manual intervention may be required")

if __name__ == "__main__":
    main()