#!/usr/bin/env python3
"""
Script to fix url_alias values in the content table on production server.
Removes "articles/" and "photobooks/" prefixes from url_alias values.
"""

import sys
import os
from agents.ssh_agent import SSHAgent
from datetime import datetime

def main():
    """Main function to fix url_alias values"""
    
    # Initialize SSH agent
    ssh_agent = SSHAgent()
    
    try:
        # Connect to server
        print("Connecting to production server...")
        ssh_agent.connect()
        
        # Use known database credentials from config check
        print("Using database configuration...")
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
        # Format: 'password' => 'actual_password',
        import re
        match = re.search(r"'password'\s*=>\s*'([^']*)'", config_line)
        if not match:
            print("Could not extract database password")
            return False
            
        db_pass = match.group(1)
        print(f"Database: {db_name} on {db_host}")
        
        # Create backup timestamp
        backup_timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        backup_file = f"content_table_backup_{backup_timestamp}.sql"
        
        # Create backup of content table
        print(f"Creating backup of content table to {backup_file}...")
        backup_cmd = f"mysqldump -h {db_host} -u {db_user} -p'{db_pass}' {db_name} content > /home/dalthaus/{backup_file}"
        exit_code, stdout, stderr = ssh_agent.execute_command(backup_cmd)
        
        if exit_code != 0:
            print(f"Error creating backup: {stderr}")
            return False
        
        print("Backup created successfully!")
        
        # Examine current url_alias values
        print("\nExamining current url_alias values...")
        examine_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_id, title, content_type, url_alias FROM content WHERE url_alias LIKE 'articles/%' OR url_alias LIKE 'photobooks/%' ORDER BY content_type, content_id;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(examine_cmd)
        
        if exit_code != 0:
            print(f"Error examining data: {stderr}")
            return False
            
        print("BEFORE - Current url_alias values with prefixes:")
        print(stdout)
        
        # Update url_alias values to remove prefixes
        print("\nUpdating url_alias values...")
        
        # Update articles (remove "articles/" prefix)
        update_articles_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"UPDATE content SET url_alias = SUBSTRING(url_alias, 10) WHERE url_alias LIKE 'articles/%';\""
        exit_code, stdout, stderr = ssh_agent.execute_command(update_articles_cmd)
        
        if exit_code != 0:
            print(f"Error updating articles: {stderr}")
            return False
            
        # Update photobooks (remove "photobooks/" prefix)
        update_photobooks_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"UPDATE content SET url_alias = SUBSTRING(url_alias, 12) WHERE url_alias LIKE 'photobooks/%';\""
        exit_code, stdout, stderr = ssh_agent.execute_command(update_photobooks_cmd)
        
        if exit_code != 0:
            print(f"Error updating photobooks: {stderr}")
            return False
            
        print("Updates completed!")
        
        # Verify the fix
        print("\nVerifying the fix...")
        verify_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_id, title, content_type, url_alias FROM content WHERE content_type IN ('article', 'photobook') ORDER BY content_type, content_id;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(verify_cmd)
        
        if exit_code != 0:
            print(f"Error verifying data: {stderr}")
            return False
            
        print("AFTER - Updated url_alias values without prefixes:")
        print(stdout)
        
        # Show update counts
        print("\nChecking update counts...")
        count_cmd = f"mysql -h {db_host} -u {db_user} -p'{db_pass}' {db_name} -e \"SELECT content_type, COUNT(*) as count FROM content WHERE content_type IN ('article', 'photobook') GROUP BY content_type;\""
        exit_code, stdout, stderr = ssh_agent.execute_command(count_cmd)
        
        if exit_code == 0:
            print("Updated record counts:")
            print(stdout)
        
        print(f"\n✅ SUCCESS: url_alias values have been fixed!")
        print(f"📁 Backup saved as: /home/dalthaus/{backup_file}")
        
        return True
        
    except Exception as e:
        print(f"Error: {e}")
        return False
        
    finally:
        ssh_agent.disconnect()

if __name__ == "__main__":
    success = main()
    sys.exit(0 if success else 1)