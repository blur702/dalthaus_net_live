#!/usr/bin/env python3
"""
Add DirectoryIndex directive to .htaccess to ensure index.php is served
"""

import sys
from agents.ssh_agent import SSHAgent

def main():
    print("=" * 80)
    print("ADDING DirectoryIndex TO .htaccess")
    print("=" * 80)

    agent = SSHAgent()

    if not agent.connect():
        print("ERROR: Failed to connect")
        return 1

    web_root = "/home/dalthaus/public_html"
    htaccess_path = f"{web_root}/.htaccess"

    print("\n1. Reading current .htaccess...")
    success, htaccess_content = agent.read_file(htaccess_path)
    if not success:
        print(f"ERROR: Could not read .htaccess: {htaccess_content}")
        agent.disconnect()
        return 1

    print("Successfully read .htaccess")

    print("\n2. Adding DirectoryIndex directive...")

    # Add DirectoryIndex at the beginning, right after "RewriteEngine On"
    lines = htaccess_content.split('\n')
    new_lines = []
    added = False

    for line in lines:
        new_lines.append(line)
        # Add DirectoryIndex right after RewriteEngine On
        if not added and 'RewriteEngine On' in line:
            new_lines.append('')
            new_lines.append('# Specify index file order - prefer index.php')
            new_lines.append('DirectoryIndex index.php index.html')
            new_lines.append('')
            added = True

    new_htaccess_content = '\n'.join(new_lines)

    print("\n3. Uploading updated .htaccess...")
    success = agent.write_file(htaccess_path, new_htaccess_content, backup=True)
    if success:
        print("Successfully updated .htaccess")
    else:
        print("ERROR: Failed to update .htaccess")
        agent.disconnect()
        return 1

    print("\n4. Verifying the change...")
    success, updated_content = agent.read_file(htaccess_path)
    if success:
        for i, line in enumerate(updated_content.split('\n')[:30], 1):
            if 'DirectoryIndex' in line or 'RewriteEngine' in line:
                print(f"Line {i}: {line}")

    print("\n" + "=" * 80)
    print("UPDATE COMPLETE!")
    print("=" * 80)
    print("\nThe .htaccess now includes:")
    print("  DirectoryIndex index.php index.html")
    print("\nThis tells the web server to prefer index.php over index.html")
    print("\nNext steps:")
    print("  1. The site should now serve index.php by default")
    print("  2. If still showing 'Under Construction', purge Cloudflare cache")
    print("  3. Test: http://dalthaus.net/")

    agent.disconnect()
    return 0

if __name__ == "__main__":
    sys.exit(main())
