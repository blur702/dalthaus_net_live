# Remote File Agent Setup Guide

## Overview
The Remote File Agent allows Claude (or any authorized client) to read, write, and manage files on your shared hosting server without SSH access.

## How It Works

1. **PHP Agent** (`remote-file-agent.php`) runs on your server and handles file operations
2. **Daily Token** - Security token changes daily (format: `agent-YYYYMMDD`)
3. **JSON API** - All communication happens via JSON over HTTPS
4. **Path Validation** - All operations restricted to website directory

## Setup Instructions

### Step 1: Upload the Agent
Upload `remote-file-agent.php` to your server root (same directory as index.php)

### Step 2: Test the Agent
Visit: `https://dalthaus.net/remote-file-agent.php?action=info&token=agent-YYYYMMDD`
(Replace YYYYMMDD with today's date)

You should see server information in JSON format.

### Step 3: Security (Optional but Recommended)

1. **Add IP Whitelist** - Edit the agent file and add your IP:
```php
$ALLOWED_IPS = ['YOUR.IP.ADDRESS.HERE'];
```

2. **Change Token Pattern** - Modify the token generation:
```php
$VALID_TOKEN = 'your-custom-token-' . date('Ymd');
```

3. **Restrict Base Directory** - Limit to specific folders:
```php
$BASE_DIR = realpath(__DIR__ . '/specific-folder');
```

## Available Operations

### Read File
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=read" \
  -d "path=includes/config.php" \
  -d "token=agent-20250830"
```

### Write File
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=write" \
  -d "path=test.txt" \
  -d "content=Hello World" \
  -d "token=agent-20250830"
```

### List Directory
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=list" \
  -d "path=." \
  -d "token=agent-20250830"
```

### Delete File
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=delete" \
  -d "path=test.txt" \
  -d "token=agent-20250830"
```

### Check Existence
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=exists" \
  -d "path=index.php" \
  -d "token=agent-20250830"
```

### Create Directory
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=mkdir" \
  -d "path=new-folder" \
  -d "token=agent-20250830"
```

### Change Permissions
```bash
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=chmod" \
  -d "path=test.txt" \
  -d "mode=0644" \
  -d "token=agent-20250830"
```

## Python Client Usage

### Basic Setup
```python
from remote_client import RemoteFileAgent

agent = RemoteFileAgent('https://dalthaus.net/remote-file-agent.php')
```

### Read a File
```python
content = agent.read('includes/config.php')
print(content)
```

### Write a File
```python
agent.write('test.txt', 'Hello from Python!')
```

### List Files
```python
files = agent.list_files('includes')
for f in files:
    print(f"{f['type']}: {f['name']}")
```

## How Claude Can Use This

When I need to work with files on your server, I can:

1. **Read files** to understand the current state
2. **Write/update files** to fix issues or add features
3. **Create new files** for new functionality
4. **Delete old files** during cleanup
5. **Check file existence** before operations
6. **List directories** to understand structure
7. **Set permissions** to fix access issues

Example workflow:
```python
# Check current config
config = agent.read('includes/config.php')

# Make modifications
new_config = config.replace('old_value', 'new_value')

# Write back
agent.write('includes/config.php', new_config)

# Verify
if agent.exists('includes/config.php'):
    print("Config updated successfully")
```

## Security Considerations

1. **Token Rotation** - Token changes daily automatically
2. **HTTPS Only** - Always use HTTPS to prevent token interception
3. **Path Restriction** - Agent cannot access files outside website directory
4. **IP Whitelist** - Optional but recommended for production
5. **Remove When Done** - Delete the agent when not actively needed

## Troubleshooting

### 401 Unauthorized
- Check token is correct for today's date
- Format: `agent-YYYYMMDD` (e.g., `agent-20250830`)

### 403 Forbidden
- IP not in whitelist (if configured)
- Check `$ALLOWED_IPS` array in agent

### Invalid Path
- Path trying to access outside website directory
- Use relative paths from website root

### File Not Found
- Check file exists with `exists` action first
- Verify path is correct

## Testing the Agent

Run the Python client test:
```bash
python3 remote-client.py
```

This will:
1. Get server info
2. Write a test file
3. Read it back
4. Check it exists
5. List directory
6. Delete test file

## Important Notes

- **Daily Token**: Remember the token changes at midnight (server time)
- **File Paths**: Use forward slashes (/) even on Windows
- **Permissions**: Agent runs with web server permissions
- **Large Files**: Consider chunking for files over 10MB
- **Backups**: Always backup before major changes

## Removing the Agent

When you no longer need remote access:
```bash
# Via the agent itself
curl -X POST https://dalthaus.net/remote-file-agent.php \
  -d "action=delete" \
  -d "path=remote-file-agent.php" \
  -d "token=agent-20250830"
```

Or simply delete via FTP/cPanel.