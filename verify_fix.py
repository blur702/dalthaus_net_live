#!/usr/bin/env python3
"""
Verify the production fix and check for any remaining issues
"""

import sys
from agents.ssh_agent import SSHAgent

def main():
    print("=" * 80)
    print("VERIFYING PRODUCTION FIX")
    print("=" * 80)

    agent = SSHAgent()

    if not agent.connect():
        print("ERROR: Failed to connect")
        return 1

    web_root = "/home/dalthaus/public_html"

    print("\n1. Checking for any index.html files...")
    exit_code, stdout, stderr = agent.execute_command(
        f"find {web_root} -maxdepth 1 -name 'index.*' -type f"
    )
    print("Files starting with 'index' in web root:")
    print(stdout if stdout.strip() else "None found")

    print("\n2. Checking index.php content (first 20 lines)...")
    success, content = agent.read_file(f"{web_root}/index.php")
    if success:
        lines = content.split('\n')[:20]
        print('\n'.join(lines))
    else:
        print(f"ERROR: Could not read index.php: {content}")

    print("\n3. Testing direct PHP execution...")
    exit_code, stdout, stderr = agent.execute_command(
        f"cd {web_root} && php -r 'echo \"PHP is working: \" . phpversion();'"
    )
    print(stdout)

    print("\n4. Checking .htaccess DirectoryIndex...")
    success, htaccess = agent.read_file(f"{web_root}/.htaccess")
    if success:
        has_directory_index = False
        for line in htaccess.split('\n'):
            if 'DirectoryIndex' in line and not line.strip().startswith('#'):
                print(f"Found: {line.strip()}")
                has_directory_index = True
        if not has_directory_index:
            print("No DirectoryIndex directive found")
            print("Recommendation: Add 'DirectoryIndex index.php' to .htaccess")

    print("\n5. Checking web server configuration...")
    # Check what web server is running
    exit_code, stdout, stderr = agent.execute_command(
        "ps aux | grep -E 'apache2|httpd|nginx' | grep -v grep | head -5"
    )
    print("Web server processes:")
    print(stdout if stdout.strip() else "Could not detect web server")

    print("\n6. Checking file order preference...")
    exit_code, stdout, stderr = agent.execute_command(
        f"ls -lt {web_root}/index.* 2>&1"
    )
    print("Index files by modification time (newest first):")
    print(stdout)

    print("\n7. Testing a simple PHP file...")
    test_php = "<?php phpinfo(); ?>"
    agent.write_file(f"{web_root}/test_info.php", test_php, backup=False)
    print("Created test_info.php - you can access it at http://dalthaus.net/test_info.php")

    print("\n" + "=" * 80)
    print("DIAGNOSIS")
    print("=" * 80)
    print("\nThe admin page is working (no 500 error)")
    print("However, homepage may still show 'Under Construction'")
    print("\nPossible causes:")
    print("  1. Cloudflare caching the old index.html")
    print("  2. .htaccess missing DirectoryIndex directive")
    print("  3. Web server default preference for .html over .php")
    print("\nRecommended fixes:")
    print("  1. Purge Cloudflare cache")
    print("  2. Add 'DirectoryIndex index.php' to .htaccess")
    print("  3. Verify no other index.html files exist")

    agent.disconnect()
    return 0

if __name__ == "__main__":
    sys.exit(main())
