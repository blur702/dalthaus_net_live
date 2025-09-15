#!/usr/bin/env python3
"""
SSH Agent Configuration Parsing Example
Demonstrates the enhanced SSH agent with automatic config parsing capabilities
"""

from ssh_agent import SSHAgent
import json


def main():
    """Demonstrate enhanced SSH agent configuration parsing features"""
    
    # NOTE: Replace these with your actual server credentials
    HOST = "your-server.com"
    USERNAME = "your-username"
    PASSWORD = "your-password"
    
    # Create SSH agent
    agent = SSHAgent(HOST, USERNAME, PASSWORD)
    
    # Connect to server
    print("🔌 Connecting to server...")
    if not agent.connect():
        print("❌ Failed to connect to server")
        return
    
    print("✅ Connected successfully!")
    print("=" * 60)
    
    # Example 1: Detect environment and config files
    print("\n📍 1. ENVIRONMENT DETECTION")
    print("Detecting environment and available config files...")
    
    app_path = "/var/www/html"  # Adjust this to your application path
    env_info = agent.detect_environment(app_path)
    
    print(f"Environment: {env_info['environment']}")
    print(f"Config files found: {len(env_info['config_files'])}")
    for config_file in env_info['config_files']:
        print(f"  📄 {config_file}")
    
    # Use the first config file found for examples
    if not env_info['config_files']:
        print("❌ No config files found. Creating example with common path...")
        config_file = f"{app_path}/config/config.php"
    else:
        config_file = env_info['config_files'][0]
    
    print("=" * 60)
    
    # Example 2: Parse PHP configuration file
    print("\n📖 2. PARSING PHP CONFIGURATION")
    print(f"Parsing configuration file: {config_file}")
    
    config = agent.parse_config_file(config_file)
    
    if config:
        print("✅ Configuration parsed successfully!")
        print(f"Configuration sections found: {list(config.keys())}")
        
        # Show app configuration (safe to display)
        if 'app' in config:
            print("\nApplication Configuration:")
            app_config = config['app']
            safe_app_config = {k: v for k, v in app_config.items() 
                             if k not in ['secret_key', 'api_key']}
            print(json.dumps(safe_app_config, indent=2))
    else:
        print("❌ Failed to parse configuration file")
    
    print("=" * 60)
    
    # Example 3: Extract database configuration
    print("\n🗄️  3. DATABASE CONFIGURATION EXTRACTION")
    print("Extracting database configuration...")
    
    db_config = agent.get_database_config(config_file)
    
    if db_config:
        print("✅ Database configuration extracted!")
        # Hide password for security
        safe_db_config = db_config.copy()
        if 'password' in safe_db_config and safe_db_config['password']:
            safe_db_config['password'] = '*' * min(8, len(safe_db_config['password']))
        
        print(json.dumps(safe_db_config, indent=2))
    else:
        print("❌ No database configuration found")
    
    print("=" * 60)
    
    # Example 4: Test database connection using config
    print("\n🔗 4. DATABASE CONNECTION TEST")
    print("Testing database connection using extracted config...")
    
    success, used_config = agent.connect_to_database_from_config(config_file)
    
    if success:
        print("✅ Successfully connected to database!")
        print(f"Connected to: {used_config.get('host')}/{used_config.get('database')}")
    else:
        print("❌ Failed to connect to database")
        if used_config:
            print("Config was extracted but connection failed")
        else:
            print("No valid database config found")
    
    print("=" * 60)
    
    # Example 5: Smart MySQL query using server config
    print("\n💾 5. SMART DATABASE QUERIES")
    
    if success:
        print("Executing smart queries using server configuration...")
        
        # Example queries (adjust table names for your database)
        queries = [
            "SHOW TABLES;",
            "SELECT COUNT(*) as total_users FROM users;",
            "SHOW STATUS LIKE 'Uptime';",
            "SELECT DATABASE() as current_database;"
        ]
        
        for query in queries:
            print(f"\n📊 Query: {query}")
            query_success, result = agent.smart_mysql_query(query, config_file)
            
            if query_success:
                print("✅ Query executed successfully:")
                # Format output nicely
                lines = result.strip().split('\n')
                for line in lines[-10:]:  # Show last 10 lines to avoid spam
                    if line.strip() and not line.startswith('+') and 'mysql:' not in line.lower():
                        print(f"   {line}")
            else:
                print(f"❌ Query failed: {result}")
    else:
        print("⏭️  Skipping database queries (no connection)")
    
    print("=" * 60)
    
    # Example 6: Configuration caching
    print("\n💾 6. CONFIGURATION CACHING")
    print("Demonstrating configuration caching...")
    
    # Parse the same config again - should use cache
    print("Parsing config again (should use cache)...")
    cached_config = agent.parse_config_file(config_file)
    
    print("Cache status demonstrated in logs above ⬆️")
    
    # Clear cache
    print("Clearing configuration cache...")
    agent.clear_config_cache()
    
    print("=" * 60)
    
    # Example 7: Multiple config file formats
    print("\n📁 7. MULTIPLE CONFIG FORMATS")
    print("Checking for different config file formats...")
    
    # Look for different types of config files
    config_patterns = [
        f"{app_path}/.env",
        f"{app_path}/.env.production",
        f"{app_path}/config/database.json",
        f"{app_path}/config/app.json"
    ]
    
    for pattern in config_patterns:
        print(f"\nChecking: {pattern}")
        
        # Check if file exists
        exit_code, stdout, stderr = agent.execute_command(f"test -f '{pattern}' && echo 'exists'", show_output=False)
        
        if exit_code == 0 and 'exists' in stdout:
            print(f"✅ Found: {pattern}")
            # Try to parse it
            try:
                format_config = agent.parse_config_file(pattern)
                if format_config:
                    print(f"   📊 Parsed successfully, {len(format_config)} top-level keys")
                else:
                    print("   ⚠️  File exists but parsing failed")
            except Exception as e:
                print(f"   ❌ Error parsing: {e}")
        else:
            print(f"⏭️  Not found: {pattern}")
    
    print("=" * 60)
    
    # Example 8: High-level convenience commands
    print("\n🚀 8. HIGH-LEVEL CONVENIENCE OPERATIONS")
    print("Demonstrating high-level operations...")
    
    # Quick database status using config
    print("\n🏥 Database health check using server config:")
    if db_config:
        mysql_status = agent.check_mysql_status(
            user=db_config.get('username', 'root'),
            password=db_config.get('password', ''),
            host=db_config.get('host', 'localhost')
        )
        
        print(f"MySQL Running: {'✅' if mysql_status.get('running') else '❌'}")
        if mysql_status.get('version'):
            print(f"Version: {mysql_status['version']}")
        print(f"Databases: {len(mysql_status.get('databases', []))}")
    
    # System info for context
    print("\n🖥️  System information:")
    system_info = agent.get_system_info()
    
    if 'os' in system_info:
        print(f"OS: {system_info['os']}")
    if 'uptime' in system_info:
        print(f"Uptime: {system_info['uptime']}")
    
    print("=" * 60)
    print("\n🎉 CONFIGURATION PARSING DEMO COMPLETE!")
    print("\nThe SSH agent now supports:")
    print("• ✅ Automatic PHP config parsing")
    print("• ✅ Database credential extraction") 
    print("• ✅ Smart database connections")
    print("• ✅ Multi-format config support (PHP/JSON/env)")
    print("• ✅ Environment detection")
    print("• ✅ Configuration caching")
    print("• ✅ High-level convenience commands")
    
    # Disconnect
    agent.disconnect()
    print("\n👋 Disconnected from server")


if __name__ == "__main__":
    print("SSH Agent Enhanced Configuration Parsing Demo")
    print("=" * 60)
    print("This script demonstrates the enhanced SSH agent capabilities")
    print("for automatically reading and parsing server configuration files.")
    print()
    print("⚠️  IMPORTANT: Update the server credentials in this script")
    print("   before running!")
    print("=" * 60)
    
    # Uncomment the next line and add your credentials to run the demo
    # main()
    
    print("\n💡 To run this demo:")
    print("1. Edit the HOST, USERNAME, PASSWORD variables in main()")
    print("2. Uncomment the main() call at the bottom")
    print("3. Run: python ssh_agent_config_example.py")