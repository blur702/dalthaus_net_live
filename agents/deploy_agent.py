#!/usr/bin/env python3
"""
Deployment Agent for dalthaus.net CMS
Handles deployments, database operations, and server management
"""

import sys
import os

# Add current directory and scripts/deployment to path
current_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.join(os.path.dirname(current_dir), 'scripts', 'deployment')
sys.path.insert(0, current_dir)
sys.path.insert(0, scripts_dir)

from ssh_agent import SSHAgent

class DeploymentAgent:
    """High-level deployment agent for the CMS"""
    
    def __init__(self):
        self.web_root = os.getenv("WEB_ROOT")
        self.config_path = os.getenv("CONFIG_PATH")
        self.agent = SSHAgent()
    
    def connect(self):
        """Connect to the server"""
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
    
    def pull_latest_code(self, branch="main"):
        """Pull latest code from GitHub"""
        print(f"\n--- Pulling latest code from GitHub ({branch}) ---")
        
        success = self.agent.git_pull(self.web_root, branch)
        
        if success:
            print("[SUCCESS] Code pulled successfully!")
        else:
            print("[ERROR] Git pull failed!")
        
        return success
    
    def check_git_status(self):
        """Check git repository status"""
        print("\n--- Checking git status ---")
        
        try:
            status_info = self.agent.git_status(self.web_root)
            
            if status_info:
                print("Git Repository Status:")
                print(f"  Branch: {status_info.get('current_branch', 'Unknown')}")
                print(f"  Last Commit: {status_info.get('last_commit', {}).get('message', 'Unknown')}")
                print(f"  Author: {status_info.get('last_commit', {}).get('author', 'Unknown')}")
                print(f"  Date: {status_info.get('last_commit', {}).get('date', 'Unknown')}")
                
                if status_info.get('modified_files'):
                    print(f"  Modified Files: {len(status_info['modified_files'])}")
                    for file in status_info['modified_files'][:5]:  # Show first 5
                        print(f"    - {file}")
                
                if status_info.get('untracked_files'):
                    print(f"  Untracked Files: {len(status_info['untracked_files'])}")
                    for file in status_info['untracked_files'][:5]:  # Show first 5
                        print(f"    - {file}")
                
                return True
            else:
                print("[ERROR] Could not get git status")
                return False
                
        except Exception as e:
            print(f"[ERROR] Git status check failed: {e}")
            return False
    
    def get_database_config(self):
        """Get database configuration from server config file"""
        print("\n--- Reading database configuration ---")
        
        try:
            config = self.agent.get_database_config(self.config_path)
            if config:
                print("[SUCCESS] Database configuration found:")
                print(f"  Host: {config['host']}")
                print(f"  Database: {config['database']}")
                print(f"  Username: {config['username']}")
                print(f"  Password: [HIDDEN]")
                return config
            else:
                print("[ERROR] Could not read database configuration")
                return None
        except Exception as e:
            print(f"[ERROR] Error reading config: {e}")
            return None
    
    def test_database_connection(self):
        """Test database connection using server config"""
        print("\n--- Testing database connection ---")

        try:
            result = self.agent.connect_to_database_from_config(self.config_path)

            # Handle both tuple return and potential boolean return for backward compatibility
            if isinstance(result, tuple):
                success, config = result
            else:
                success = result
                config = {}

            if success:
                print("[SUCCESS] Database connection successful!")

                # Test a simple query - smart_mysql_query returns (bool, str)
                query_success, query_output = self.agent.smart_mysql_query("SHOW TABLES", config_path=self.config_path)
                if query_success and query_output:
                    # Parse the output to show table names
                    lines = query_output.strip().split('\n')
                    if len(lines) > 1:  # Skip header if present
                        tables = [line.strip() for line in lines if line.strip() and not line.startswith('Tables_in')]
                        if tables:
                            print(f"Found {len(tables)} database tables")
                            for table in tables[:5]:  # Show first 5 tables
                                print(f"  - {table}")

                return True
            else:
                print("[ERROR] Database connection failed!")
                return False

        except Exception as e:
            print(f"[ERROR] Database test error: {e}")
            return False
    
    def health_check(self):
        """Perform comprehensive health check"""
        print("\n=== SERVER HEALTH CHECK ===")
        
        try:
            health = self.agent.health_check()
            
            print("System Status:")
            print(f"  Uptime: {health.get('uptime', 'Unknown')}")
            print(f"  Load Average: {health.get('load_avg', 'Unknown')}")
            print(f"  Memory Usage: {health.get('memory_usage', 'Unknown')}")
            print(f"  Disk Usage: {health.get('disk_usage', 'Unknown')}")
            
            return health
            
        except Exception as e:
            print(f"[ERROR] Health check error: {e}")
            return None
    
    def check_index_files(self):
        """Check for index files on production server"""
        print("\n=== CHECKING INDEX FILES ON PRODUCTION SERVER ===")
        
        try:
            # 1. Check for any index files
            print("\n1. Checking for index.* files:")
            result = self.agent.execute_command('ls -la /home/dalthaus/public_html/index.*')
            print(f"Result: {result}")
            
            # 2. Check current working directory structure
            print("\n2. Checking web root directory structure:")
            result = self.agent.execute_command('ls -la /home/dalthaus/public_html/ | head -20')
            print(f"Directory listing:\n{result}")
            
            # 3. Check git status specifically for index files
            print("\n3. Checking git status for index files:")
            result = self.agent.execute_command('cd /home/dalthaus/public_html && git status | grep -i index')
            print(f"Git status (index files): {result}")
            
            # 4. Check if there are any backup files
            print("\n4. Checking for backup files:")
            result = self.agent.execute_command('ls -la /home/dalthaus/public_html/*.backup* 2>/dev/null || echo "No backup files found"')
            print(f"Backup files:\n{result}")
            
            # 5. Check .htaccess for any rewrite rules
            print("\n5. Checking .htaccess for index file directives:")
            result = self.agent.execute_command('cd /home/dalthaus/public_html && grep -i "directoryindex\\|index" .htaccess || echo "No index directives found"')
            print(f".htaccess index directives:\n{result}")
            
            # 6. Check if index.html exists in git history
            print("\n6. Checking git log for index.html:")
            result = self.agent.execute_command('cd /home/dalthaus/public_html && git log --oneline -5 --follow -- index.html')
            print(f"Git log for index.html:\n{result}")
            
            # 7. Verify current commit matches local
            print("\n7. Current commit on server:")
            result = self.agent.execute_command('cd /home/dalthaus/public_html && git log -1 --format="%H %s"')
            print(f"Current commit:\n{result}")
            
            # 8. Check what Apache is actually serving
            print("\n8. Testing what file Apache is serving:")
            result = self.agent.execute_command('cd /home/dalthaus/public_html && find . -name "index.*" -type f')
            print(f"All index files found:\n{result}")
            
            return True
            
        except Exception as e:
            print(f"[ERROR] Index file check error: {e}")
            return False
    
    def execute_command(self, command):
        """Execute arbitrary command on server"""
        print(f"\n--- Executing: {command} ---")
        
        try:
            exit_code, stdout, stderr = self.agent.execute_command(command)
            
            if exit_code == 0:
                print("[SUCCESS] Command executed successfully!")
                if stdout:
                    print("Output:")
                    print(stdout)
            else:
                print(f"[ERROR] Command failed with exit code {exit_code}")
                if stderr:
                    print("Error:")
                    print(stderr)
                if stdout:
                    print("Output:")
                    print(stdout)
            
            return exit_code == 0, stdout, stderr
            
        except Exception as e:
            print(f"[ERROR] Command execution error: {e}")
            return False, "", str(e)
    
    def setup_backup_script(self):
        """Deploy and setup the MySQL backup script"""
        print("\n=== SETTING UP MYSQL BACKUP SCRIPT ===")
        
        # Create backup script content that reads from PHP array config file
        backup_script = '''#!/bin/bash

# MySQL Database Backup Script
# Runs every 4 hours via cron
# Keeps 48 hours (12 backups) of SQL dumps

# Extract database configuration from PHP config file
CONFIG_FILE="/home/dalthaus/public_html/config/config.php"

# Parse database credentials from PHP array format config.php
DB_HOST=$(grep "'host'" "$CONFIG_FILE" | sed "s/.*'host' => '\([^']*\)'.*/\\1/")
DB_NAME=$(grep "'dbname'" "$CONFIG_FILE" | sed "s/.*'dbname' => '\([^']*\)'.*/\\1/")
DB_USER=$(grep "'username'" "$CONFIG_FILE" | sed "s/.*'username' => '\([^']*\)'.*/\\1/")
DB_PASS=$(grep "'password'" "$CONFIG_FILE" | sed "s/.*'password' => '\([^']*\)'.*/\\1/")

# Backup directory (outside web root)
BACKUP_DIR="/home/dalthaus/mysql_backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${DB_NAME}_backup_${DATE}.sql"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILE}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Log the backup attempt
echo "$(date): Starting backup for database: $DB_NAME (user: $DB_USER)"

# Create the MySQL dump
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_PATH"

# Check if backup was successful
if [ $? -eq 0 ] && [ -s "$BACKUP_PATH" ]; then
    echo "$(date): Backup successful - $BACKUP_FILE ($(du -h "$BACKUP_PATH" | cut -f1))"
    
    # Compress the backup file to save space
    gzip "$BACKUP_PATH"
    echo "$(date): Backup compressed - ${BACKUP_FILE}.gz ($(du -h "${BACKUP_PATH}.gz" | cut -f1))"
    
    # Remove backups older than 48 hours (keep 12 backups, 4-hour intervals)
    find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" -type f -mtime +2 -delete
    
    # Log cleanup
    REMAINING=$(find "$BACKUP_DIR" -name "${DB_NAME}_backup_*.sql.gz" -type f | wc -l)
    echo "$(date): Cleanup complete - $REMAINING backup files remaining"
    
else
    echo "$(date): Backup failed for database $DB_NAME!" >&2
    if [ -f "$BACKUP_PATH" ]; then
        echo "$(date): Backup file size: $(du -h "$BACKUP_PATH" | cut -f1)"
        rm -f "$BACKUP_PATH"  # Clean up empty backup file
    fi
    exit 1
fi
'''
        
        if not self.connect():
            return False
        
        try:
            # Create the backup script on the server
            script_path = '/home/dalthaus/mysql_backup.sh'
            
            # Write the script content
            success, stdout, stderr = self.execute_command(f'cat > {script_path} << \'EOF\'\n{backup_script}EOF')
            if not success:
                print(f"[ERROR] Failed to create backup script: {stderr}")
                return False
            
            # Make the script executable
            success, stdout, stderr = self.execute_command(f'chmod +x {script_path}')
            if not success:
                print(f"[ERROR] Failed to make script executable: {stderr}")
                return False
            
            # Create backup directory
            success, stdout, stderr = self.execute_command('mkdir -p /home/dalthaus/mysql_backups')
            if not success:
                print(f"[ERROR] Failed to create backup directory: {stderr}")
                return False
            
            print("[SUCCESS] MySQL backup script deployed successfully")
            print(f"📍 Script location: {script_path}")
            print("📁 Backup directory: /home/dalthaus/mysql_backups")
            print("\n🕐 CRON JOB SETUP INSTRUCTIONS:")
            print("1. Log into cPanel")
            print("2. Go to 'Cron Jobs' under 'Advanced'")
            print("3. Add a new cron job with these settings:")
            print("   - Minute: 0")
            print("   - Hour: */4")
            print("   - Day: *")
            print("   - Month: *")
            print("   - Weekday: *")
            print(f"   - Command: {script_path}")
            print("\nThis will run the backup every 4 hours and keep 48 hours worth of backups.")
            
            return True
            
        except Exception as e:
            print(f"[ERROR] Failed to setup backup script: {str(e)}")
            return False
        finally:
            self.disconnect()
    
    def test_backup(self):
        """Test the backup script"""
        print("\n=== TESTING BACKUP SCRIPT ===")
        
        if not self.connect():
            return False
        
        try:
            script_path = '/home/dalthaus/mysql_backup.sh'
            
            # Check if script exists
            success, stdout, stderr = self.execute_command(f'test -f {script_path} && echo "Script exists" || echo "Script not found"')
            if "Script not found" in stdout:
                print("[ERROR] Backup script not found. Run 'setup-backup' first.")
                return False
            
            # Run the backup script
            print("Running backup script...")
            success, stdout, stderr = self.execute_command(script_path)
            
            if success:
                print("[SUCCESS] Backup test completed")
                print("Output:", stdout)
            else:
                print("[ERROR] Backup test failed")
                print("Error:", stderr)
            
            return success
            
        except Exception as e:
            print(f"[ERROR] Backup test error: {str(e)}")
            return False
        finally:
            self.disconnect()
    
    def list_backups(self):
        """List available backup files"""
        print("\n=== LISTING BACKUP FILES ===")
        
        if not self.connect():
            return False
        
        try:
            success, stdout, stderr = self.execute_command('ls -lah /home/dalthaus/mysql_backups/')
            
            if success:
                print("Available backups:")
                print(stdout)
            else:
                print("[ERROR] Failed to list backups")
                print("Error:", stderr)
            
            return success
            
        except Exception as e:
            print(f"[ERROR] List backups error: {str(e)}")
            return False
        finally:
            self.disconnect()

    def deploy(self, branch="main"):
        """Full deployment process"""
        print("\n[DEPLOY] STARTING DEPLOYMENT")
        print("=" * 50)
        
        if not self.connect():
            return False
        
        try:
            # 1. Check current status
            self.check_git_status()
            
            # 2. Pull latest code
            if not self.pull_latest_code(branch):
                print("[ERROR] Deployment failed at code pull step")
                return False
            
            # 3. Test database
            if not self.test_database_connection():
                print("[WARNING] Database connection failed, but continuing...")
            
            # 4. Health check
            self.health_check()
            
            print("\n[SUCCESS] DEPLOYMENT COMPLETED SUCCESSFULLY!")
            return True
            
        except Exception as e:
            print(f"[ERROR] Deployment error: {e}")
            return False
        
        finally:
            self.disconnect()

def main():
    """Main CLI interface"""
    
    if len(sys.argv) < 2:
        print("Usage:")
        print("  python deploy_agent.py deploy [branch]    - Deploy code")
        print("  python deploy_agent.py status            - Check git status")
        print("  python deploy_agent.py pull [branch]     - Pull code only")
        print("  python deploy_agent.py db                - Test database")
        print("  python deploy_agent.py health            - Health check")
        print("  python deploy_agent.py checkindex        - Check index files")
        print("  python deploy_agent.py setup-backup      - Setup MySQL backup script")
        print("  python deploy_agent.py test-backup       - Test backup script")
        print("  python deploy_agent.py list-backups      - List backup files")
        print("  python deploy_agent.py exec 'command'    - Execute arbitrary command")
        return
    
    command = sys.argv[1].lower()
    branch = sys.argv[2] if len(sys.argv) > 2 else "main"
    
    agent = DeploymentAgent()
    
    if command == "deploy":
        agent.deploy(branch)
    
    elif command == "status":
        if agent.connect():
            agent.check_git_status()
            agent.disconnect()
    
    elif command == "pull":
        if agent.connect():
            agent.pull_latest_code(branch)
            agent.disconnect()
    
    elif command == "db":
        if agent.connect():
            agent.get_database_config()
            agent.test_database_connection()
            agent.disconnect()
    
    elif command == "health":
        if agent.connect():
            agent.health_check()
            agent.disconnect()
    
    elif command == "checkindex":
        if agent.connect():
            agent.check_index_files()
            agent.disconnect()
    
    elif command == "setup-backup":
        agent.setup_backup_script()
    
    elif command == "test-backup":
        agent.test_backup()
    
    elif command == "list-backups":
        agent.list_backups()
    
    elif command == "exec":
        if len(sys.argv) < 3:
            print("Error: exec command requires a command to execute")
            print("Usage: python deploy_agent.py exec 'command'")
            return
        
        command_to_execute = sys.argv[2]
        if agent.connect():
            agent.execute_command(command_to_execute)
            agent.disconnect()
    
    else:
        print(f"Unknown command: {command}")

if __name__ == "__main__":
    main()