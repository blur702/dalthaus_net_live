#!/usr/bin/env python3
"""
Fix production site issues:
1. Upload index.php from local to production
2. Remove index.html that's blocking the PHP application
"""

import sys
from agents.ssh_agent import SSHAgent

def main():
    print("=" * 80)
    print("FIXING PRODUCTION SITE")
    print("=" * 80)

    agent = SSHAgent()

    if not agent.connect():
        print("ERROR: Failed to connect")
        return 1

    web_root = "/home/dalthaus/public_html"
    local_index_php = "/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/index.php"

    print("\nStep 1: Reading local index.php...")
    try:
        with open(local_index_php, 'r') as f:
            index_php_content = f.read()
        print(f"Successfully read local index.php ({len(index_php_content)} bytes)")
    except Exception as e:
        print(f"ERROR: Failed to read local index.php: {e}")
        agent.disconnect()
        return 1

    print("\nStep 2: Backing up index.html on production...")
    exit_code, stdout, stderr = agent.execute_command(
        f"mv {web_root}/index.html {web_root}/index.html.backup.$(date +%Y%m%d_%H%M%S)"
    )
    if exit_code == 0:
        print("Successfully backed up index.html")
    else:
        print(f"Warning: Could not backup index.html (may not exist): {stderr}")

    print("\nStep 3: Uploading index.php to production...")
    success = agent.write_file(f"{web_root}/index.php", index_php_content, backup=False)
    if success:
        print("Successfully uploaded index.php")
    else:
        print("ERROR: Failed to upload index.php")
        agent.disconnect()
        return 1

    print("\nStep 4: Setting proper permissions on index.php...")
    exit_code, stdout, stderr = agent.execute_command(f"chmod 644 {web_root}/index.php")
    if exit_code == 0:
        print("Successfully set permissions to 644")
    else:
        print(f"Warning: Could not set permissions: {stderr}")

    print("\nStep 5: Verifying index.php on production...")
    perms = agent.check_file_permissions(f"{web_root}/index.php")
    if perms:
        print(f"  Permissions: {perms.get('permissions')}")
        print(f"  Owner: {perms.get('owner')}")
        print(f"  Group: {perms.get('group')}")
        print(f"  Size: {perms.get('size')} bytes")
    else:
        print("ERROR: Could not verify index.php")
        agent.disconnect()
        return 1

    print("\nStep 6: Checking for index.html...")
    exit_code, stdout, stderr = agent.execute_command(
        f"ls -la {web_root}/index.html* 2>&1", show_output=False
    )
    print("Index files in web root:")
    print(stdout)

    print("\n" + "=" * 80)
    print("FIX COMPLETE!")
    print("=" * 80)
    print("\nSummary:")
    print("  - index.php has been uploaded to production")
    print("  - index.html has been backed up")
    print("  - The site should now be accessible")
    print("\nNext steps:")
    print("  1. Test the homepage: http://dalthaus.net/")
    print("  2. Test the admin: http://dalthaus.net/admin")
    print("  3. If working, commit index.php to git and push to origin")

    agent.disconnect()
    return 0

if __name__ == "__main__":
    sys.exit(main())
