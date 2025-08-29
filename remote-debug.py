#!/usr/bin/env python3
"""
Remote Debugging Client for Dalthaus CMS
Connects to remote-agent.php to diagnose and fix issues
"""

import requests
import json
import sys
from datetime import datetime

# Configuration
REMOTE_URL = "https://dalthaus.net/remote-agent.php"
TOKEN = f"debug-{datetime.now().strftime('%Y%m%d')}"

def make_request(action, data=None):
    """Make a request to the remote agent"""
    params = {'token': TOKEN, 'action': action}
    if data:
        response = requests.post(REMOTE_URL, params=params, data=data)
    else:
        response = requests.get(REMOTE_URL, params=params)
    
    if response.status_code == 403:
        print(f"❌ Access denied. Check token: {TOKEN}")
        return None
    
    try:
        return response.json()
    except:
        print(f"❌ Invalid response: {response.text[:500]}")
        return None

def main():
    print("=" * 60)
    print("Remote Debugging Client for Dalthaus CMS")
    print(f"Target: {REMOTE_URL}")
    print(f"Token: {TOKEN}")
    print("=" * 60)
    
    # 1. Get server info
    print("\n📋 Getting server information...")
    info = make_request('info')
    if info and info['status'] == 'ok':
        data = info['data']
        print(f"✅ PHP Version: {data['php_version']}")
        print(f"✅ Server: {data['server_software']}")
        print(f"✅ Document Root: {data['document_root']}")
        print(f"✅ Current User: {data['user']}")
        
        # Check required extensions
        required = ['pdo', 'pdo_mysql', 'session', 'json']
        extensions = data['extensions']
        for ext in required:
            if ext in extensions:
                print(f"✅ Extension {ext}: Loaded")
            else:
                print(f"❌ Extension {ext}: NOT LOADED")
    
    # 2. Check for errors
    print("\n📋 Checking error logs...")
    errors = make_request('check_errors')
    if errors and errors['status'] == 'ok':
        for log_name, log_data in errors['data'].items():
            if log_data['exists']:
                print(f"\n📄 {log_name}:")
                if 'last_10_lines' in log_data:
                    for line in log_data['last_10_lines']:
                        print(f"  {line.strip()}")
    
    # 3. Check files
    print("\n📋 Checking critical files...")
    files = make_request('check_files')
    if files and files['status'] == 'ok':
        for file, data in files['data'].items():
            if isinstance(data, dict):
                print(f"✅ {file}: exists (perms: {data['perms']})")
            else:
                print(f"❌ {file}: not found")
    
    # 4. Read .htaccess
    print("\n📋 Reading .htaccess...")
    htaccess = make_request('read_file', {'file': '.htaccess'})
    if htaccess and htaccess['status'] == 'ok':
        content = htaccess['data']['content']
        print(f"Current .htaccess ({htaccess['data']['lines']} lines)")
        # Check for problematic directives
        if 'Options' in content and '<IfModule' not in content.split('Options')[0]:
            print("⚠️  Found unprotected Options directive - this often causes 500 errors!")
            
    # 5. Offer to fix
    print("\n" + "=" * 60)
    print("🔧 Available fixes:")
    print("1. Replace .htaccess with minimal version")
    print("2. Get full phpinfo()")
    print("3. Test database connection")
    print("4. Self-destruct agent")
    print("0. Exit")
    
    choice = input("\nEnter choice (0-4): ")
    
    if choice == '1':
        print("Fixing .htaccess...")
        fix = make_request('fix_htaccess')
        if fix and fix['status'] == 'ok':
            print("✅ " + fix['data']['message'])
    elif choice == '2':
        phpinfo = make_request('phpinfo')
        with open('phpinfo.html', 'w') as f:
            f.write(phpinfo['data']['phpinfo'])
        print("✅ Saved phpinfo to phpinfo.html")
    elif choice == '3':
        host = input("DB Host [localhost]: ") or 'localhost'
        name = input("DB Name: ")
        user = input("DB User: ")
        pass_input = input("DB Pass: ")
        test = make_request('test_db', {
            'host': host, 'name': name, 
            'user': user, 'pass': pass_input
        })
        if test:
            print(test['data']['message'] if test['data']['connected'] else test['data']['error'])
    elif choice == '4':
        if input("Delete remote agent? (yes/no): ") == 'yes':
            make_request('self_destruct')
            print("✅ Agent deleted")

if __name__ == "__main__":
    main()