#!/usr/bin/env python3
"""
Toggle maintenance mode on/off
"""

import sys
import os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent
from ssh_config import SSH_CONFIG

def toggle_maintenance_mode(enable=None):
    """Toggle maintenance mode on or off"""
    
    print("=== Maintenance Mode Toggle ===\n")
    
    agent = SSHAgent(SSH_CONFIG["host"], SSH_CONFIG["username"], SSH_CONFIG["password"], SSH_CONFIG["port"])
    
    if not agent.connect():
        print("[ERROR] Could not connect to server")
        return
    
    try:
        # Get database config
        config = agent.get_database_config(SSH_CONFIG["config_path"])
        if not config:
            print("[ERROR] Could not get database config")
            return
        
        # Get current status
        print("1. Checking current maintenance mode status...")
        
        check_script = f"""
mysql -h {config['host']} -u {config['username']} -p'{config['password']}' {config['database']} << 'EOF'
SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode';
EOF
"""
        
        script_path = "/tmp/check_maintenance.sh"
        agent.write_file(script_path, check_script)
        agent.execute_command(f"chmod +x {script_path}")
        success, output, error = agent.execute_command(f"bash {script_path}")
        agent.execute_command(f"rm -f {script_path}")
        
        current_value = "0"
        if success and output:
            lines = output.strip().split('\n')
            for line in lines:
                if line.strip() in ['0', '1']:
                    current_value = line.strip()
                    break
        
        current_status = "ENABLED" if current_value == "1" else "DISABLED"
        print(f"   Current status: {current_status} (value: {current_value})")
        
        # Determine new value
        if enable is None:
            new_value = "0" if current_value == "1" else "1"
        else:
            new_value = "1" if enable else "0"
        
        new_status = "ENABLED" if new_value == "1" else "DISABLED"
        
        if new_value == current_value:
            print(f"   No change needed - maintenance mode is already {current_status}")
            return
        
        # Update maintenance mode
        print(f"\n2. Setting maintenance mode to {new_status}...")
        
        update_script = f"""
mysql -h {config['host']} -u {config['username']} -p'{config['password']}' {config['database']} << 'EOF'
UPDATE settings SET setting_value = '{new_value}' WHERE setting_key = 'maintenance_mode';
SELECT 'Updated successfully' as result;
SELECT setting_key, setting_value FROM settings WHERE setting_key = 'maintenance_mode';
EOF
"""
        
        script_path = "/tmp/update_maintenance.sh"
        agent.write_file(script_path, update_script)
        agent.execute_command(f"chmod +x {script_path}")
        success, output, error = agent.execute_command(f"bash {script_path}")
        agent.execute_command(f"rm -f {script_path}")
        
        if success:
            print("   Database update result:")
            print(output)
            print(f"   [SUCCESS] Maintenance mode is now {new_status}")
        else:
            print(f"   [ERROR] Update failed: {error}")
            
    except Exception as e:
        print(f"[ERROR] Toggle failed: {e}")
    
    finally:
        agent.disconnect()

if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description='Toggle maintenance mode')
    parser.add_argument('--enable', action='store_true', help='Enable maintenance mode')
    parser.add_argument('--disable', action='store_true', help='Disable maintenance mode')
    
    args = parser.parse_args()
    
    if args.enable and args.disable:
        print("ERROR: Cannot specify both --enable and --disable")
        sys.exit(1)
    
    enable = None
    if args.enable:
        enable = True
    elif args.disable:
        enable = False
    
    toggle_maintenance_mode(enable)