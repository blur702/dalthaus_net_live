#!/usr/bin/env python3
"""
Script to verify the final state of url_alias values in the content table
"""

import sys
import os
from agents.ssh_agent import SSHAgent

def main():
    """Main function to verify final state"""
    
    # Initialize SSH agent
    ssh_agent = SSHAgent()
    
    try:
        # Connect to server
        print("Connecting to production server...")
        ssh_agent.connect()
        
        # Database credentials
        db_host = "localhost"
        db_name = "dalthaus_maincms"
        db_user = "dalthaus_maincms"
        
        # Get password from config file
        exit_code, stdout, stderr = ssh_agent.execute_command("cd /home/dalthaus/public_html && grep \"'password'\" config/config.php | head -1")
        if exit_code != 0:
            print(f"Error reading config: {stderr}")
            return False
            
        # Extract password from the line
        config_line = stdout.strip()
        import re
        match = re.search(r"'password'\s*=>\s*'([^']*)'", config_line)
        if not match:
            print("Could not extract database password")
            return False
            
        db_pass = match.group(1)
        print(f"Database: {db_name} on {db_host}")
        
        # Check all content records
        print("\nChecking all content records...")
        all_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_id, title, content_type, url_alias FROM content ORDER BY content_type, content_id;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(all_cmd)
        
        if exit_code != 0:
            print(f"Error checking all records: {stderr}")
            return False
            
        print("All content records:")
        print(stdout)
        
        # Check for any remaining prefixed url_alias values
        print("\nChecking for any remaining prefixed url_alias values...")
        prefix_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_id, title, content_type, url_alias FROM content WHERE url_alias LIKE '%/%' ORDER BY content_type, content_id;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(prefix_cmd)
        
        if exit_code != 0:
            print(f"Error checking prefixed values: {stderr}")
            return False
            
        if stdout.strip():
            print("Records with slashes in url_alias (may be legitimate):")
            print(stdout)
        else:
            print("No records found with slashes in url_alias values.")
        
        # Count by content type
        print("\nContent count by type:")
        count_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_type, COUNT(*) as count FROM content GROUP BY content_type;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(count_cmd)
        
        if exit_code == 0:
            print(stdout)
        
        return True
        
    except Exception as e:
        print(f"Error: {e}")
        return False
        
    finally:
        ssh_agent.disconnect()

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)