#!/usr/bin/env python3
"""
Deployment Agent for dalthaus.net CMS
Handles deployments, database operations, and server management
"""

import sys
import os

# Add current directory to path so we can import ssh_agent
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent

class DeploymentAgent:
    """High-level deployment agent for the CMS"""
    
    def __init__(self):
        # Load SSH configuration
        try:
            from ssh_config import SSH_CONFIG
            self.host = SSH_CONFIG["host"]
            self.username = SSH_CONFIG["username"]
            self.password = SSH_CONFIG["password"]
            self.port = SSH_CONFIG["port"]
            self.web_root = SSH_CONFIG["web_root"]
            self.config_path = SSH_CONFIG["config_path"]
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
    
    def pull_latest_code(self, branch="main"):
        """Pull latest code from GitHub"""
        print(f"\n--- Pulling latest code from GitHub ({branch}) ---")
        
        success, output, error = self.agent.git_pull(self.web_root, branch)
        
        if success:
            print("[SUCCESS] Code pulled successfully!")
            print("Changes:")
            print(output)
        else:
            print("[ERROR] Git pull failed!")
            print(f"Error: {error}")
        
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
        print("  python deploy_agent.py deploy [branch]  - Deploy code")
        print("  python deploy_agent.py status          - Check git status")
        print("  python deploy_agent.py pull [branch]   - Pull code only")
        print("  python deploy_agent.py db              - Test database")
        print("  python deploy_agent.py health          - Health check")
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
    
    else:
        print(f"Unknown command: {command}")

if __name__ == "__main__":
    main()