# SSH Deployment Agent

A powerful SSH agent for automated deployment and server management of the dalthaus.net CMS.

## 🚀 Features

- **Secure SSH connections** to production servers
- **Git operations** (pull, status, branch management)
- **Database configuration parsing** from server config files
- **Health monitoring** and system checks
- **Automated deployment workflows**

## 📋 Setup

### 1. Install Dependencies
```bash
pip install paramiko
```

### 2. Configure SSH Credentials
```bash
# Copy the template
cp ssh_config.template.py ssh_config.py

# Edit ssh_config.py with your server details
# NOTE: ssh_config.py is gitignored and will not be committed
```

### 3. Configure ssh_config.py
```python
SSH_CONFIG = {
    "host": "your-server.com",
    "username": "your-username",
    "password": "your-password", 
    "port": 22,  # or custom SSH port
    "web_root": "/path/to/web/root",
    "config_path": "/path/to/config.php"
}
```

## 🛠 Usage

### Basic Commands

```bash
# Check git status on server
python deploy_agent.py status

# Pull latest code from GitHub  
python deploy_agent.py pull [branch]

# Full deployment workflow
python deploy_agent.py deploy [branch]

# Test database configuration
python deploy_agent.py db

# Server health check
python deploy_agent.py health
```

### Development Workflow

1. **Make changes locally**
2. **Commit and push to GitHub**
3. **Deploy to server:**
   ```bash
   python deploy_agent.py deploy main
   ```

## 🔧 Advanced Usage

### Programmatic Usage
```python
from deploy_agent import DeploymentAgent

agent = DeploymentAgent()
if agent.connect():
    # Pull latest code
    agent.pull_latest_code('main')
    
    # Check database config
    config = agent.get_database_config()
    
    # Run health check
    health = agent.health_check()
    
    agent.disconnect()
```

### SSH Agent Methods
```python
from ssh_agent import SSHAgent

agent = SSHAgent(host, username, password, port)
if agent.connect():
    # Execute commands
    success, output, error = agent.execute_command('ls -la')
    
    # Git operations
    status = agent.git_status('/path/to/repo')
    agent.git_pull('/path/to/repo', 'main')
    
    # Config parsing
    config = agent.parse_config_file('/path/to/config.php')
    db_config = agent.get_database_config('/path/to/config.php')
    
    # Database operations
    agent.connect_to_database_from_config('/path/to/config.php')
    result = agent.smart_mysql_query("SHOW TABLES", config_path='/path/to/config.php')
    
    agent.disconnect()
```

## 🔒 Security

- **Local Only**: The SSH agent runs entirely on your local machine
- **Encrypted Connection**: Uses SSH encryption for all communication
- **Credential Protection**: SSH credentials are kept in local files only
- **No Remote Code**: No deployment scripts are installed on the production server

## 📁 Files

- `ssh_agent.py` - Core SSH agent with all functionality
- `deploy_agent.py` - High-level deployment automation
- `ssh_config.template.py` - Configuration template
- `ssh_config.py` - Your actual credentials (gitignored)
- `requirements.txt` - Python dependencies

## 🚨 Important Notes

- **Never commit ssh_config.py** - It contains your credentials
- **Keep credentials secure** - Only store them locally
- **Test connections** - Always test with `status` command first
- **Check git state** - Review changes before deploying

## 🔍 Troubleshooting

### Connection Issues
```bash
# Test basic connectivity
python -c "import socket; s=socket.socket(); s.settimeout(5); print('Port open' if s.connect_ex(('your-host', 7822))==0 else 'Port closed'); s.close()"
```

### Configuration Issues
```bash
# Verify config file
python -c "from ssh_config import SSH_CONFIG; print(SSH_CONFIG)"
```

### Git Issues
```bash
# Check git status manually
python deploy_agent.py status
```

## 📚 Examples

### Deploy Latest Code
```bash
python deploy_agent.py deploy main
```

### Check What Changed
```bash
python deploy_agent.py status
```

### Test Database Connection
```bash
python deploy_agent.py db
```

The SSH agent provides a secure, efficient way to manage your production server without compromising security or requiring server-side deployment scripts.