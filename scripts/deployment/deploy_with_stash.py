#!/usr/bin/env python3
"""
Deploy with stash - handles local changes before pulling
"""

import sys
import os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent
from ssh_config import SSH_CONFIG

def main():
    # Connect to server
    print("Connecting to server...")
    agent = SSHAgent(
        SSH_CONFIG["host"],
        SSH_CONFIG["username"],
        SSH_CONFIG["password"],
        SSH_CONFIG["port"]
    )

    if not agent.connect():
        print("[ERROR] Failed to connect to server")
        return 1

    print("[SUCCESS] Connected to server")

    # Navigate to web root
    web_root = SSH_CONFIG["web_root"]

    # Stash local changes
    print("\n--- Stashing local changes ---")
    result = agent.execute_command(f"cd {web_root} && git stash")
    if result[0]:
        print(f"Output: {result[0]}")
    if result[1]:
        print(f"Error: {result[1]}")

    # Pull latest changes
    print("\n--- Pulling latest changes from GitHub ---")
    result = agent.execute_command(f"cd {web_root} && git pull origin main")
    if result[0]:
        print(f"Output: {result[0]}")
    if result[1]:
        print(f"Error: {result[1]}")

    # Show current commit
    print("\n--- Current commit ---")
    result = agent.execute_command(f"cd {web_root} && git log -1 --oneline")
    if result[0]:
        print(f"Output: {result[0]}")

    # Disconnect
    agent.disconnect()
    print("\n[SUCCESS] Deployment complete!")
    return 0

if __name__ == "__main__":
    sys.exit(main())