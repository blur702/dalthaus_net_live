#!/usr/bin/env python3
"""
SSH Configuration Template
Copy this file to ssh_config.py and fill in your actual credentials
"""

# SSH Connection Settings
SSH_CONFIG = {
    "host": "your-server-hostname.com",
    "username": "your-username", 
    "password": "your-password",
    "port": 22,  # or your custom SSH port
    "web_root": "/path/to/your/web/root",
    "config_path": "/path/to/your/web/root/config/config.php"
}

# Example for A2 Hosting:
# SSH_CONFIG = {
#     "host": "server.a2hosting.com",
#     "username": "your-username",
#     "password": "your-password", 
#     "port": 7822,
#     "web_root": "/home/username/public_html",
#     "config_path": "/home/username/public_html/config/config.php"
# }