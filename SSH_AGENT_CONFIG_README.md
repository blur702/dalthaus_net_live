# Enhanced SSH Agent with Configuration Parsing

The SSH Agent has been enhanced with comprehensive configuration parsing capabilities to automatically read and parse database configuration from server config files. This eliminates the need for manual credential management and makes database operations seamless.

## New Features

### 1. Configuration File Reading & Parsing

#### Supported Formats
- **PHP Config Files** (`.php`) - Returns associative arrays
- **JSON Config Files** (`.json`) - Standard JSON format
- **Environment Files** (`.env`, `.environment`) - Key=value format

#### Auto-Detection
The agent automatically detects config file format by:
- File extension (`.php`, `.json`, `.env`)
- Content analysis (PHP tags, JSON structure, key=value pairs)

### 2. Smart Database Connection

#### Automatic Credential Extraction
```python
# Extract database credentials from server config
db_config = agent.get_database_config('/var/www/html/config/config.php')
```

#### Smart Connection Testing
```python
# Test database connection using extracted config
success, config = agent.connect_to_database_from_config('/var/www/html/config/config.php')
```

#### Fallback Support
- Automatically falls back to manual credentials if config parsing fails
- Supports both local and remote database connections

### 3. Enhanced Database Operations

#### Smart Query Execution
```python
# Execute query using server config credentials
success, result = agent.smart_mysql_query(
    "SHOW TABLES", 
    config_path='/var/www/html/config/config.php'
)
```

#### Multiple Config Sources
```python
# Use specific config file
agent.smart_mysql_query("SELECT * FROM users", config_path='/app/config/database.php')

# Fall back to manual credentials
agent.smart_mysql_query("SELECT * FROM users", user='root', password='secret')
```

### 4. Environment Detection

#### Automatic Environment Discovery
```python
# Detect environment and locate config files
env_info = agent.detect_environment('/var/www/html')
print(f"Environment: {env_info['environment']}")  # production/staging/development
print(f"Config files: {env_info['config_files']}")
```

#### Common Paths Checked
- `/config/config.php`
- `/config/database.php`
- `/.env` and variants
- `/wp-config.php` (WordPress)
- `/configuration.php` (Joomla)

### 5. Configuration Caching

#### Performance Optimization
- Parsed configurations are cached automatically
- Reduces file reading and parsing overhead
- Cache can be cleared when needed

```python
# Clear cache when config changes
agent.clear_config_cache()
```

## Usage Examples

### Basic Configuration Parsing

```python
from ssh_agent import SSHAgent

# Connect to server
agent = SSHAgent("server.com", "username", "password")
agent.connect()

# Parse any config file
config = agent.parse_config_file("/var/www/html/config/config.php")
print(f"Sections: {list(config.keys())}")

# Extract database config specifically
db_config = agent.get_database_config("/var/www/html/config/config.php")
print(f"Database: {db_config['database']} on {db_config['host']}")
```

### Smart Database Operations

```python
# Execute queries using server config
success, result = agent.smart_mysql_query(
    "SELECT COUNT(*) FROM users",
    config_path="/var/www/html/config/config.php"
)

if success:
    print("Query result:", result)
```

### Environment Detection and Auto-Config

```python
# Detect environment and use appropriate config
env_info = agent.detect_environment("/var/www/html")

if env_info['config_files']:
    main_config = env_info['config_files'][0]
    
    # Test database connection
    success, db_config = agent.connect_to_database_from_config(main_config)
    
    if success:
        print(f"Connected to {db_config['database']}")
        
        # Run health check
        status = agent.check_mysql_status(
            user=db_config['username'],
            password=db_config['password'],
            host=db_config['host']
        )
        print(f"MySQL running: {status['running']}")
```

## CLI Commands

The SSH agent CLI has been enhanced with new configuration commands:

### Configuration Commands
```bash
# Parse any config file
config parse /var/www/html/config/config.php

# Extract database configuration
config db /var/www/html/config/config.php

# Test database connection using config
config connect /var/www/html/config/config.php

# Detect environment and config files
config detect /var/www/html

# Clear configuration cache
config cache clear
```

### Smart MySQL Commands
```bash
# Execute query using server config
mysql smart "SHOW TABLES" /var/www/html/config/config.php

# Traditional query (still supported)
mysql query "SHOW TABLES" database_name
```

## Configuration File Examples

### PHP Configuration (Supported)
```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'my_database',
        'username' => 'db_user',
        'password' => 'db_password',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    'app' => [
        'name' => 'My Application',
        'debug' => false,
        'timezone' => 'America/New_York'
    ]
];
```

### JSON Configuration (Supported)
```json
{
    "database": {
        "host": "localhost",
        "dbname": "my_database",
        "username": "db_user",
        "password": "db_password",
        "port": 3306
    },
    "app": {
        "name": "My Application",
        "debug": false
    }
}
```

### Environment File (Supported)
```env
DB_HOST=localhost
DB_NAME=my_database
DB_USER=db_user
DB_PASSWORD=db_password
DB_PORT=3306

APP_NAME="My Application"
APP_DEBUG=false
```

## High-Level Operations

### Complete Database Health Check
```python
def database_health_check(agent, config_path):
    """Complete database health check using server config"""
    
    # Extract database config
    db_config = agent.get_database_config(config_path)
    if not db_config:
        return "No database config found"
    
    # Test connection
    success, _ = agent.connect_to_database_from_config(config_path)
    if not success:
        return "Database connection failed"
    
    # Check MySQL status
    status = agent.check_mysql_status(
        user=db_config['username'],
        password=db_config['password'],
        host=db_config['host']
    )
    
    return {
        'connection': 'OK',
        'mysql_running': status.get('running', False),
        'version': status.get('version'),
        'databases': len(status.get('databases', []))
    }
```

### Application Deployment with Config Validation
```python
def deploy_with_config_check(agent, app_path):
    """Deploy application with configuration validation"""
    
    # Detect environment
    env_info = agent.detect_environment(app_path)
    print(f"Environment: {env_info['environment']}")
    
    # Validate database config
    if env_info['config_files']:
        main_config = env_info['config_files'][0]
        success, db_config = agent.connect_to_database_from_config(main_config)
        
        if not success:
            print("❌ Database configuration invalid")
            return False
        
        print(f"✅ Database config valid: {db_config['database']}")
    
    # Proceed with deployment
    return agent.deploy_code(app_path)
```

## Security Features

### Password Protection
- Database passwords are automatically masked in output
- Configuration cache doesn't store sensitive data in logs
- Credentials are only used for connections, not displayed

### Safe Parsing
- PHP code is not executed, only parsed statically
- No eval() or dangerous operations
- Handles malformed config files gracefully

## Performance Optimizations

### Caching
- Parsed configurations are cached per file path
- Cache is automatically used for repeated requests
- Manual cache clearing available

### Efficient Parsing
- Minimal regex processing
- Streaming file reading
- Early termination on parse errors

## Error Handling

### Graceful Degradation
- Falls back to manual credentials if config parsing fails
- Provides detailed error messages for debugging
- Continues operation even with partial config failures

### Debugging Support
- Comprehensive logging at INFO level
- Parse error details in ERROR level logs
- Cache hit/miss information for performance tuning

## Limitations

### PHP Parser Limitations
- Simplified PHP parser - doesn't handle all PHP syntax
- Complex string concatenation may not parse perfectly
- PHP constants (like PDO::ATTR_*) are treated as strings
- Nested arrays with complex expressions may be partially parsed

### Workarounds
- Focus on essential database configuration extraction
- Manual fallback always available
- Parser improvements can be added incrementally

## Testing

### Local Testing
```bash
# Test configuration parser locally
python test_config_parser.py
```

### Remote Testing
```bash
# Test with actual server (update credentials first)
python ssh_agent_config_example.py
```

## Migration Guide

### From Manual Credentials
```python
# Before: Manual credentials
agent.mysql_query("SHOW TABLES", "mydb", "user", "password", "localhost")

# After: Automatic config
agent.smart_mysql_query("SHOW TABLES", config_path="/app/config/config.php")
```

### CLI Migration
```bash
# Before: Manual MySQL commands
mysql query "SHOW TABLES" mydb

# After: Smart commands
mysql smart "SHOW TABLES" /app/config/config.php
```

## Best Practices

### Configuration File Management
1. Keep database configs in standard locations (`/config/config.php`)
2. Use consistent array structure for database settings
3. Separate sensitive production configs from development

### Usage Patterns
1. Always try smart operations first, fall back to manual
2. Use environment detection for multi-environment deployments
3. Clear cache after configuration changes

### Security Recommendations
1. Protect config files with appropriate permissions
2. Use environment-specific configs for different stages
3. Avoid hardcoding credentials in deployment scripts

## Future Enhancements

### Planned Features
- Enhanced PHP parser for complex syntax
- Support for additional config formats (YAML, INI)
- Config file validation and linting
- Integration with popular frameworks (Laravel, Symfony)

### Extensibility
- Plugin system for custom config parsers
- Framework-specific config handlers
- Advanced caching strategies

---

This enhanced SSH agent provides a seamless bridge between server configuration files and database operations, making server management more efficient and less error-prone.