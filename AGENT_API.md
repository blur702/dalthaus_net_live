# Agent API Documentation

## Overview
The agent.php file provides a secure API for remote file management and Git operations on dalthaus.net. This allows Claude to directly manage the live website through HTTP requests.

## Security
- **Authentication Key**: `dalthaus_agent_key_2025` (MUST BE CHANGED!)
- **Rate Limiting**: Max 60 requests per minute
- **Protected Paths**: Cannot modify agent.php, config files, or .git directory
- **Automatic Backups**: Creates backups before modifying/deleting files

## API Endpoint
```
POST https://dalthaus.net/agent.php
Content-Type: application/json
```

## Authentication
All requests must include the authentication key:
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "...",
  ...
}
```

## Available Actions

### 1. System Status
Check if the agent is working and get system info.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "status"
}
```

### 2. Read File
Read the contents of a file.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "read",
  "path": "src/Controllers/Public/Home.php"
}
```

### 3. Write File
Create or overwrite a file.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "write",
  "path": "test.txt",
  "content": "Hello World"
}
```

### 4. Edit File
Find and replace text in a file.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "edit",
  "path": "config/routes.php",
  "search": "old text",
  "replace": "new text"
}
```

### 5. Delete File
Delete a file (creates backup first).
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "delete",
  "path": "temp.txt"
}
```

### 6. List Directory
List files in a directory.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "list",
  "path": "src/Views"
}
```

### 7. Check File Exists
Check if a file or directory exists.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "exists",
  "path": "uploads/test.jpg"
}
```

### 8. Git Pull
Pull latest changes from GitHub repository.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "git_pull"
}
```

### 9. Git Status
Check Git repository status.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "git_status"
}
```

### 10. Create Backup
Backup a file or entire site.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "backup",
  "path": "config/config.php"
}
```

For full site backup:
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "backup"
}
```

### 11. Restore Backup
Restore from a backup.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "restore",
  "backup": "config.php.backup.20250113120000"
}
```

### 12. Execute Command
Run whitelisted shell commands.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "exec",
  "command": "php -v"
}
```

Allowed commands: ls, pwd, whoami, php -v, mysql --version, df -h, free -m, uptime, date

### 13. Get Logs
Retrieve log files.
```json
{
  "key": "dalthaus_agent_key_2025",
  "action": "logs",
  "type": "agent",
  "lines": 50
}
```

Log types: agent, error, access

## Response Format
All responses are JSON:
```json
{
  "success": true/false,
  "message": "Description of result",
  "data": {
    // Action-specific data
  },
  "timestamp": "2025-01-13T12:00:00+00:00"
}
```

## Workflow Example

### Claude's Workflow:
1. **Check Status**: Verify agent is working
2. **Read File**: Get current file content
3. **Edit/Write**: Make necessary changes
4. **Git Status**: Check what changed
5. **Test locally**: Fix any issues in local environment
6. **Git Push**: Push fixes to repository
7. **Git Pull**: Tell agent to pull latest changes

### Communication Pattern:
```
Claude (local) → Fix issue → Push to GitHub
       ↓
Claude → Agent API → git_pull
       ↓
Live site updated on dalthaus.net
```

## Setup Instructions

1. **Upload agent.php** to dalthaus.net root directory

2. **Change the authentication key** in agent.php:
   ```php
   define('AGENT_KEY', 'your_secure_random_key_here');
   ```

3. **Set up Git** on the server:
   ```bash
   cd /path/to/website
   git init
   git remote add origin https://github.com/blur702/dalthaus_net_live.git
   git fetch origin
   git checkout -b main origin/main
   ```

4. **Create logs directory**:
   ```bash
   mkdir logs
   chmod 755 logs
   ```

5. **Protect agent.php** in .htaccess (optional):
   ```apache
   <Files "agent.php">
     # Allow only specific IPs
     # Order Deny,Allow
     # Deny from all
     # Allow from your.ip.address
   </Files>
   ```

## Security Recommendations

1. **Change the default key immediately**
2. **Use HTTPS only** for agent requests
3. **Restrict access by IP** if possible
4. **Monitor agent.log** regularly
5. **Disable agent when not in use** by setting `AGENT_ENABLED` to false
6. **Delete agent.php** after major deployments

## Testing the Agent

Test with curl:
```bash
curl -X POST https://dalthaus.net/agent.php \
  -H "Content-Type: application/json" \
  -d '{"key":"dalthaus_agent_key_2025","action":"status"}'
```

## Error Codes
- **401**: Invalid authentication key
- **429**: Rate limit exceeded
- **400**: Bad request (missing parameters)
- **500**: Server error

## Important Notes
- All file operations create automatic backups
- Git pull does a hard reset to origin/main
- Protected files cannot be modified
- Rate limited to 60 requests per minute
- Sessions used for rate limiting