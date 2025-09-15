#!/usr/bin/env python3
"""
SSH Agent Usage Examples
Demonstrates how to use the SSH Agent for various remote server operations
"""

from ssh_agent import SSHAgent
import json


def example_usage():
    """Example showing how to use the SSH Agent programmatically"""
    
    # Configuration - replace with your actual server details
    config = {
        'host': 'your-server.com',
        'username': 'your-username',
        'password': 'your-password',
        'port': 22
    }
    
    # Create and connect to SSH agent
    agent = SSHAgent(**config)
    
    try:
        # Connect to the server
        if not agent.connect():
            print("Failed to connect to server")
            return
        
        print("Connected to server successfully!")
        
        # Example 1: System health check
        print("\n" + "="*50)
        print("SYSTEM HEALTH CHECK")
        print("="*50)
        
        health = agent.health_check()
        print(f"Overall Status: {health['overall_status']}")
        print(f"Timestamp: {health['timestamp']}")
        
        # Example 2: Git operations
        print("\n" + "="*50)
        print("GIT OPERATIONS")
        print("="*50)
        
        # Check git status
        git_status = agent.git_status("/path/to/your/repo")
        print(f"Current branch: {git_status.get('current_branch', 'unknown')}")
        print(f"Has changes: {git_status.get('has_changes', False)}")
        
        if git_status.get('latest_commit'):
            commit = git_status['latest_commit']
            print(f"Latest commit: {commit['hash']} by {commit['author']} - {commit['message']}")
        
        # Pull latest changes
        print("\nPulling latest changes...")
        if agent.git_pull("/path/to/your/repo", "main"):
            print("Git pull successful")
        else:
            print("Git pull failed")
        
        # Example 3: Database operations
        print("\n" + "="*50)
        print("DATABASE OPERATIONS")
        print("="*50)
        
        # Check MySQL status
        mysql_status = agent.check_mysql_status(user="your_db_user", password="your_db_password")
        print(f"MySQL running: {mysql_status['running']}")
        if mysql_status['version']:
            print(f"MySQL version: {mysql_status['version']}")
        print(f"Databases: {mysql_status['databases']}")
        
        # Execute a simple query
        success, result = agent.mysql_query(
            "SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'your_database'",
            database="your_database",
            user="your_db_user",
            password="your_db_password"
        )
        
        if success:
            print("Query executed successfully:")
            print(result)
        else:
            print(f"Query failed: {result}")
        
        # Example 4: File operations
        print("\n" + "="*50)
        print("FILE OPERATIONS")
        print("="*50)
        
        # List directory contents
        files = agent.list_directory("/var/www/html")
        print(f"Found {len(files)} items in /var/www/html:")
        for file_info in files[:5]:  # Show first 5 items
            file_type = "DIR" if file_info['is_directory'] else "FILE"
            print(f"  {file_type}: {file_info['name']} ({file_info['size']} bytes)")
        
        # Read a configuration file
        config_file = "/etc/mysql/mysql.conf.d/mysqld.cnf"
        success, content = agent.read_file(config_file)
        if success:
            print(f"\nFirst 200 chars of {config_file}:")
            print(content[:200] + "..." if len(content) > 200 else content)
        else:
            print(f"Could not read {config_file}: {content}")
        
        # Example 5: System monitoring
        print("\n" + "="*50)
        print("SYSTEM MONITORING")
        print("="*50)
        
        # Get system information
        system_info = agent.get_system_info()
        print(f"OS: {system_info.get('os', 'unknown')}")
        print(f"Uptime: {system_info.get('uptime', 'unknown')}")
        print(f"Current directory: {system_info.get('current_directory', 'unknown')}")
        
        # Check specific services
        services_to_check = ['apache2', 'mysql', 'ssh']
        for service in services_to_check:
            status = agent.check_service_status(service)
            status_text = "RUNNING" if status['running'] else "NOT RUNNING"
            print(f"Service {service}: {status_text}")
        
        # Find specific processes
        processes = agent.find_process('apache2')
        print(f"\nFound {len(processes)} Apache processes:")
        for proc in processes[:3]:  # Show first 3
            print(f"  PID {proc['pid']}: {proc['command'][:80]}...")
        
        # Example 6: Custom commands
        print("\n" + "="*50)
        print("CUSTOM COMMANDS")
        print("="*50)
        
        # Execute custom commands
        commands = [
            "whoami",
            "pwd",
            "date",
            "free -h"
        ]
        
        for cmd in commands:
            exit_code, stdout, stderr = agent.execute_command(cmd, show_output=False)
            print(f"{cmd}: {stdout.strip()}")
        
        # Example 7: Complete deployment workflow
        print("\n" + "="*50)
        print("DEPLOYMENT WORKFLOW")
        print("="*50)
        
        repo_path = "/var/www/html/your-app"
        
        # 1. Check current status
        git_status = agent.git_status(repo_path)
        print(f"Pre-deployment status: {git_status.get('current_branch', 'unknown')} branch")
        
        # 2. Deploy code (git pull + restart services)
        services_to_restart = ['apache2']
        success = agent.deploy_code(repo_path, "main", services_to_restart)
        print(f"Deployment {'successful' if success else 'failed'}")
        
        # 3. Verify deployment
        post_status = agent.git_status(repo_path)
        if post_status.get('latest_commit'):
            commit = post_status['latest_commit']
            print(f"Now running: {commit['hash']} - {commit['message']}")
        
        # 4. Health check after deployment
        health = agent.health_check()
        print(f"Post-deployment health: {health['overall_status']}")
        
    except Exception as e:
        print(f"Error during operations: {e}")
    
    finally:
        # Always disconnect
        agent.disconnect()
        print("\nDisconnected from server")


def simple_example():
    """Simple example for quick tasks"""
    
    # Quick connection for simple tasks
    agent = SSHAgent('your-server.com', 'username', 'password', 22)
    
    if agent.connect():
        try:
            # Quick git pull
            agent.git_pull('/var/www/html/myapp')
            
            # Check if a service is running
            status = agent.check_service_status('apache2')
            print(f"Apache is {'running' if status['running'] else 'not running'}")
            
            # Execute a quick command
            exit_code, output, error = agent.execute_command('uptime')
            print(f"Server uptime: {output.strip()}")
            
        finally:
            agent.disconnect()


def monitoring_example():
    """Example for continuous monitoring"""
    
    agent = SSHAgent('your-server.com', 'username', 'password', 22)
    
    if agent.connect():
        try:
            # Comprehensive health check
            health = agent.health_check({
                'mysql': {'user': 'root', 'password': 'your_password'},
                'services': ['apache2', 'mysql', 'ssh'],
                'disk_threshold': 85
            })
            
            print(json.dumps(health, indent=2))
            
            # Log to file for monitoring
            with open('server_health.json', 'w') as f:
                json.dump(health, f, indent=2)
            
        finally:
            agent.disconnect()


if __name__ == "__main__":
    print("SSH Agent Examples")
    print("Note: Update the server configuration before running!")
    print("\nChoose an example to run:")
    print("1. Full feature demonstration")
    print("2. Simple tasks example")
    print("3. Monitoring example")
    
    choice = input("\nEnter choice (1-3): ").strip()
    
    if choice == "1":
        example_usage()
    elif choice == "2":
        simple_example()
    elif choice == "3":
        monitoring_example()
    else:
        print("Invalid choice")