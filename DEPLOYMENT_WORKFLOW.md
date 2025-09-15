# Deployment Workflow Documentation

**For: Future Claude instances, developers, and team members**

This document outlines the complete development and deployment workflow for the dalthaus.net CMS application.

## 🏗️ **Architecture Overview**

```
┌─────────────────┐    ┌──────────────┐    ┌─────────────────────┐
│ Local Development│    │   GitHub     │    │ Production Server   │
│                 │    │  Repository  │    │ (A2 Hosting)        │
│ - Code editing  │───▶│              │───▶│ - Live application  │
│ - Testing       │    │ - Version    │    │ - Database          │
│ - SSH Agent     │    │   control    │    │ - Web server        │
└─────────────────┘    │ - CI/CD      │    └─────────────────────┘
         │              └──────────────┘                │
         └─────────────── SSH Direct ───────────────────┘
              (Deploy agent pulls from GitHub)
```

## 📂 **Repository Structure**

```
dalthaus_net_live/
├── src/                    # PHP application code
├── config/                 # Configuration files
├── assets/                 # Frontend assets
├── uploads/               # User uploads
├── ssh_agent.py           # Core SSH functionality
├── deploy_agent.py        # Deployment automation
├── ssh_config.template.py # Configuration template
├── ssh_config.py          # Local credentials (gitignored)
├── CLAUDE.md             # Project instructions for Claude
├── DEPLOYMENT_WORKFLOW.md # This document
└── SSH_AGENT_README.md   # SSH agent documentation
```

## 🔄 **Complete Development Workflow**

### **Phase 1: Local Development**

1. **Setup Environment**
   ```bash
   # Clone repository
   git clone https://github.com/user/dalthaus-net-live.git
   cd dalthaus_net_live
   
   # Install SSH agent dependencies
   pip install paramiko
   
   # Configure SSH credentials
   cp ssh_config.template.py ssh_config.py
   # Edit ssh_config.py with production server credentials
   ```

2. **Local Development Server** (Optional)
   ```bash
   # Start PHP development server
   php -S localhost:8000 router.php
   
   # Setup local database (if needed)
   # See CLAUDE.md for database setup instructions
   ```

3. **Make Changes**
   - Edit PHP code in `src/`
   - Update configuration in `config/`
   - Test changes locally if needed

### **Phase 2: Version Control**

4. **Commit Changes**
   ```bash
   # Stage changes
   git add .
   
   # Commit with descriptive message
   git commit -m "feat: Add new feature description
   
   - Detailed change 1
   - Detailed change 2
   
   🤖 Generated with [Claude Code](https://claude.ai/code)
   
   Co-Authored-By: Claude <noreply@anthropic.com>"
   ```

5. **Push to GitHub**
   ```bash
   git push origin main
   ```

### **Phase 3: Deployment**

6. **Deploy to Production**
   ```bash
   # Check current server status
   python deploy_agent.py status
   
   # Deploy latest changes
   python deploy_agent.py deploy main
   ```

## 🤖 **SSH Agent Usage**

The SSH agent provides secure, automated deployment without storing credentials in version control.

### **Basic Commands**

```bash
# Check git status on production server
python deploy_agent.py status

# Pull latest code from GitHub to server
python deploy_agent.py pull [branch]

# Full deployment workflow
python deploy_agent.py deploy [branch]

# Test database configuration reading
python deploy_agent.py db

# Server health check
python deploy_agent.py health
```

### **What the Deploy Agent Does**

1. **Connects** to production server via SSH
2. **Navigates** to web root directory
3. **Pulls** latest code from GitHub
4. **Reads** server configuration files
5. **Tests** database connectivity
6. **Reports** deployment status
7. **Disconnects** securely

## 🔧 **Configuration Management**

### **SSH Configuration**

**File: `ssh_config.py` (Local only - gitignored)**
```python
SSH_CONFIG = {
    "host": "mi3-cl9-its2.a2hosting.com",
    "username": "dalthaus",
    "password": "your-password",
    "port": 7822,
    "web_root": "/home/dalthaus/public_html",
    "config_path": "/home/dalthaus/public_html/config/config.php"
}
```

### **Production Configuration**

**File: `config/config.php` (On server)**
- Database credentials for production
- Application settings
- Security configuration

**File: `config/config.local.php` (Local development only)**
- Local database credentials
- Development settings

## 🚨 **Critical Security Notes**

### **DO NOT COMMIT:**
- `ssh_config.py` - Contains SSH credentials
- Any files with passwords or API keys
- Local configuration files with credentials
- Test files with sensitive data

### **ALWAYS GITIGNORE:**
- `ssh_config.py`
- `*.local.php`
- `test_*.py` (temporary test files)
- `debug_*.py` (temporary debug files)

### **SECURE PRACTICES:**
- SSH agent runs locally only
- Credentials never leave your machine
- All communication encrypted via SSH
- No deployment scripts on production server

## 🐛 **Troubleshooting**

### **SSH Connection Issues**

```bash
# Test basic connectivity
python -c "import socket; s=socket.socket(); s.settimeout(5); print('Port open' if s.connect_ex(('mi3-cl9-its2.a2hosting.com', 7822))==0 else 'Port closed'); s.close()"

# Verify SSH config
python -c "from ssh_config import SSH_CONFIG; print(SSH_CONFIG.keys())"

# Test SSH agent connection
python deploy_agent.py status
```

### **Git Issues on Server**

```bash
# Check git status
python deploy_agent.py status

# Manual git pull
python deploy_agent.py pull main

# Check git log
ssh dalthaus@mi3-cl9-its2.a2hosting.com -p 7822
cd public_html
git log --oneline -5
```

### **Database Issues**

```bash
# Test database config reading
python deploy_agent.py db

# Check config file on server
python -c "
from ssh_agent import SSHAgent
agent = SSHAgent('mi3-cl9-its2.a2hosting.com', 'dalthaus', 'password', 7822)
if agent.connect():
    config = agent.get_database_config('/home/dalthaus/public_html/config/config.php')
    print(config)
    agent.disconnect()
"
```

## 📋 **Checklist for New Team Members**

### **Initial Setup**
- [ ] Clone repository
- [ ] Install Python dependencies (`pip install paramiko`)
- [ ] Copy `ssh_config.template.py` to `ssh_config.py`
- [ ] Configure SSH credentials in `ssh_config.py`
- [ ] Test connection: `python deploy_agent.py status`

### **Before Each Deployment**
- [ ] Test changes locally (if possible)
- [ ] Commit changes with descriptive message
- [ ] Push to GitHub
- [ ] Check server status: `python deploy_agent.py status`
- [ ] Deploy: `python deploy_agent.py deploy main`
- [ ] Verify deployment success

### **After Each Deployment**
- [ ] Test live application functionality
- [ ] Check error logs if needed
- [ ] Document any issues or changes

## 🚀 **Advanced Usage**

### **Programmatic Deployment**

```python
from deploy_agent import DeploymentAgent

def deploy_with_checks():
    agent = DeploymentAgent()
    
    if not agent.connect():
        return False
    
    try:
        # Check current status
        status = agent.check_git_status()
        print(f"Git status: {status}")
        
        # Pull latest code
        if agent.pull_latest_code('main'):
            print("Code updated successfully")
            
            # Test database
            if agent.test_database_connection():
                print("Database connection verified")
            
            # Health check
            health = agent.health_check()
            print(f"Server health: {health}")
            
            return True
        else:
            print("Code pull failed")
            return False
            
    finally:
        agent.disconnect()

# Usage
if deploy_with_checks():
    print("Deployment completed successfully")
else:
    print("Deployment failed")
```

### **Custom SSH Operations**

```python
from ssh_agent import SSHAgent

def custom_server_operation():
    agent = SSHAgent('mi3-cl9-its2.a2hosting.com', 'dalthaus', 'password', 7822)
    
    if agent.connect():
        # Execute custom commands
        success, output, error = agent.execute_command('ls -la /home/dalthaus/public_html')
        
        # Read configuration
        config = agent.parse_config_file('/home/dalthaus/public_html/config/config.php')
        
        # Database operations
        db_config = agent.get_database_config('/home/dalthaus/public_html/config/config.php')
        
        agent.disconnect()
        
        return {'command_output': output, 'config': config, 'db_config': db_config}
    
    return None
```

## 📚 **Additional Resources**

- **CLAUDE.md** - Project-specific instructions for Claude instances
- **SSH_AGENT_README.md** - Detailed SSH agent documentation
- **A2 Hosting Documentation** - Server-specific information
- **PHP Documentation** - Language reference
- **Git Documentation** - Version control reference

## 🔄 **Maintenance**

### **Regular Tasks**
- Monitor server health with `python deploy_agent.py health`
- Clean up old backup files on server
- Update dependencies as needed
- Review and rotate SSH credentials periodically

### **Emergency Procedures**
- If deployment fails, check `python deploy_agent.py status`
- For database issues, verify config with `python deploy_agent.py db`
- For SSH issues, test connection with `python deploy_agent.py status`
- Contact hosting provider for server-level issues

---

**This workflow ensures secure, reliable deployments while maintaining separation between development and production environments.**