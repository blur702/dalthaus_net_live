#!/usr/bin/env python3
"""
Manual deployment script to pull the latest code
"""

import sys
import os

# Add current directory to path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent

def main():
    try:
        from ssh_config import SSH_CONFIG
        host = SSH_CONFIG["host"]
        username = SSH_CONFIG["username"]
        password = SSH_CONFIG["password"]
        port = SSH_CONFIG["port"]
        web_root = SSH_CONFIG["web_root"]
    except ImportError:
        print("ssh_config.py not found!")
        return

    print(f"Connecting to {host}...")
    agent = SSHAgent(host, username, password, port)

    if agent.connect():
        print("Connected successfully!")

        # Force pull latest code
        print("\nPulling latest code...")
        success, output, error = agent.execute_command(f"cd {web_root} && git pull origin main")
        if success:
            print("Git pull output:")
            print(output)
        else:
            print(f"Error: {error}")

        # Check latest commit
        print("\nChecking latest commit...")
        success, output, error = agent.execute_command(f"cd {web_root} && git log -1 --oneline")
        if success:
            print("Latest commit:")
            print(output)

        agent.disconnect()
    else:
        print("Failed to connect!")

if __name__ == "__main__":
    main()