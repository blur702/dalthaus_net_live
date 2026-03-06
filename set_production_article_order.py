#!/usr/bin/env python3
"""
Set Article Order on Production Server

This script uploads the order update script to production and executes it.
"""

import sys
import os

# Add agents directory to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'agents'))

from ssh_agent import SSHAgent

def main():
    print("[SET ARTICLE ORDER] Starting...")
    print("=" * 60)

    agent = SSHAgent()

    try:
        # Connect to server
        if not agent.connect():
            print("[ERROR] Failed to connect to server")
            return False

        # Upload the script
        print("\n[UPLOAD] Uploading set_article_order.php script...")
        local_script = "scripts/set_article_order.php"
        remote_script = "/home/dalthaus/public_html/scripts/set_article_order.php"

        if agent.upload_file(local_script, remote_script):
            print("[SUCCESS] Script uploaded successfully")
        else:
            print("[ERROR] Failed to upload script")
            return False

        # Make sure scripts directory exists
        print("\n[SETUP] Ensuring scripts directory exists...")
        agent.execute_command("mkdir -p /home/dalthaus/public_html/scripts")

        # Run the script on production
        print("\n[EXECUTE] Running article order script on production...")
        print("=" * 60)

        command = "cd /home/dalthaus/public_html && echo 'yes' | php scripts/set_article_order.php 2>&1"
        output = agent.execute_command(command)

        print(output)
        print("=" * 60)

        if "✅ All changes committed successfully!" in output:
            print("\n[SUCCESS] Article order updated successfully on production!")
            return True
        else:
            print("\n[WARNING] Check output above for results")
            return False

    except Exception as e:
        print(f"[ERROR] {str(e)}")
        return False
    finally:
        agent.disconnect()

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)
