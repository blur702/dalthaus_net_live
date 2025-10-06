#!/usr/bin/env python3
"""
Production Database Backup Script

This script connects to the production server, extracts database credentials,
creates a complete SQL dump, and downloads it for local storage.

Author: Claude Code
Date: 2025-10-05
Purpose: Complete database backup before autosave system changes
"""

import os
import sys
import paramiko
import logging
from datetime import datetime
from pathlib import Path
import re
import gzip
import shutil

# Add parent directory to path to import deploy_agent
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

try:
    from dotenv import load_dotenv
    load_dotenv()
except ImportError:
    print("Warning: python-dotenv not installed. Using environment variables directly.")

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class ProductionDatabaseBackup:
    def __init__(self):
        self.host = "mi3-cl9-its2.a2hosting.com"
        self.port = 7822
        self.username = os.getenv('SSH_USER', 'dalthaus')
        self.password = os.getenv('SSH_PASS')
        self.web_root = "/home/dalthaus/public_html"
        self.config_path = f"{self.web_root}/config/config.php"
        
        # Generate timestamp for backup file
        self.timestamp = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
        self.backup_filename = f"production_backup_{self.timestamp}.sql"
        self.backup_path = f"/tmp/{self.backup_filename}"
        
        self.ssh_client = None
        self.sftp_client = None
        
        # Database credentials (will be extracted from config)
        self.db_credentials = {}

    def connect_ssh(self):
        """Establish SSH connection to production server"""
        try:
            self.ssh_client = paramiko.SSHClient()
            self.ssh_client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            logger.info(f"Connecting to {self.username}@{self.host}:{self.port}")
            self.ssh_client.connect(
                hostname=self.host,
                port=self.port,
                username=self.username,
                password=self.password,
                timeout=30
            )
            
            # Open SFTP channel
            self.sftp_client = self.ssh_client.open_sftp()
            logger.info("SSH connection established successfully")
            return True
            
        except Exception as e:
            logger.error(f"Failed to connect to server: {e}")
            return False

    def extract_database_credentials(self):
        """Extract database credentials from production config file"""
        try:
            logger.info(f"Reading config file: {self.config_path}")
            
            # Read the config file
            with self.sftp_client.open(self.config_path, 'r') as config_file:
                config_content = config_file.read()
            
            # Ensure content is string, not bytes
            if isinstance(config_content, bytes):
                config_content = config_content.decode('utf-8')
            
            logger.info("Config file read successfully")
            
            # Extract database credentials using regex
            patterns = {
                'host': r"'host'\s*=>\s*'([^']*)'",
                'database': r"'dbname'\s*=>\s*'([^']*)'",
                'username': r"'username'\s*=>\s*'([^']*)'",
                'password': r"'password'\s*=>\s*'([^']*)'",
                'charset': r"'charset'\s*=>\s*'([^']*)'",
            }
            
            for key, pattern in patterns.items():
                match = re.search(pattern, config_content)
                if match:
                    self.db_credentials[key] = match.group(1)
                    logger.info(f"Found {key}: {self.db_credentials[key] if key != 'password' else '***'}")
                else:
                    logger.warning(f"Could not find {key} in config file")
            
            # Verify we have minimum required credentials
            required_fields = ['host', 'database', 'username', 'password']
            missing_fields = [field for field in required_fields if field not in self.db_credentials]
            
            if missing_fields:
                logger.error(f"Missing required database credentials: {missing_fields}")
                return False
            
            logger.info("Database credentials extracted successfully")
            return True
            
        except Exception as e:
            logger.error(f"Failed to extract database credentials: {e}")
            return False

    def create_database_dump(self):
        """Create a complete database dump using mysqldump"""
        try:
            # Build mysqldump command with comprehensive options
            dump_cmd = f"""
            mysqldump \\
                --host='{self.db_credentials['host']}' \\
                --user='{self.db_credentials['username']}' \\
                --password='{self.db_credentials['password']}' \\
                --single-transaction \\
                --routines \\
                --triggers \\
                --events \\
                --hex-blob \\
                --add-drop-table \\
                --add-drop-database \\
                --create-options \\
                --disable-keys \\
                --extended-insert \\
                --quick \\
                --lock-tables=false \\
                --set-charset \\
                --default-character-set=utf8mb4 \\
                '{self.db_credentials['database']}' > '{self.backup_path}'
            """
            
            logger.info(f"Creating database dump: {self.backup_filename}")
            logger.info("Dump command options:")
            logger.info("  --single-transaction: Consistent backup for InnoDB tables")
            logger.info("  --routines: Include stored procedures and functions")
            logger.info("  --triggers: Include triggers")
            logger.info("  --events: Include scheduled events")
            logger.info("  --hex-blob: Handle binary data properly")
            logger.info("  --add-drop-table/database: Include DROP statements for restore")
            logger.info("  --extended-insert: Efficient INSERT statements")
            
            # Execute the mysqldump command
            stdin, stdout, stderr = self.ssh_client.exec_command(dump_cmd, timeout=300)
            
            # Wait for command to complete
            exit_status = stdout.channel.recv_exit_status()
            
            if exit_status != 0:
                error_output = stderr.read().decode('utf-8')
                logger.error(f"mysqldump failed with exit status {exit_status}")
                logger.error(f"Error output: {error_output}")
                return False
            
            # Check if backup file was created and get its size
            stdin, stdout, stderr = self.ssh_client.exec_command(f"ls -lh '{self.backup_path}'")
            file_info = stdout.read().decode('utf-8').strip()
            
            if file_info:
                logger.info(f"Backup file created successfully: {file_info}")
                return True
            else:
                logger.error("Backup file was not created")
                return False
                
        except Exception as e:
            logger.error(f"Failed to create database dump: {e}")
            return False

    def compress_backup(self):
        """Compress the backup file if it's large"""
        try:
            # Check file size first
            stdin, stdout, stderr = self.ssh_client.exec_command(f"stat -c%s '{self.backup_path}'")
            file_size = int(stdout.read().decode('utf-8').strip())
            
            # Compress if file is larger than 1MB
            if file_size > 1024 * 1024:
                compressed_path = f"{self.backup_path}.gz"
                
                logger.info(f"File size is {file_size / (1024*1024):.2f}MB, compressing...")
                
                # Compress the file
                compress_cmd = f"gzip -9 '{self.backup_path}'"
                stdin, stdout, stderr = self.ssh_client.exec_command(compress_cmd)
                exit_status = stdout.channel.recv_exit_status()
                
                if exit_status == 0:
                    self.backup_path = compressed_path
                    self.backup_filename = f"{self.backup_filename}.gz"
                    
                    # Get compressed file size
                    stdin, stdout, stderr = self.ssh_client.exec_command(f"stat -c%s '{compressed_path}'")
                    compressed_size = int(stdout.read().decode('utf-8').strip())
                    
                    compression_ratio = (1 - compressed_size / file_size) * 100
                    logger.info(f"Compression successful: {compressed_size / (1024*1024):.2f}MB ({compression_ratio:.1f}% reduction)")
                    return True
                else:
                    logger.warning("Compression failed, proceeding with uncompressed file")
                    return True
            else:
                logger.info(f"File size is {file_size / 1024:.2f}KB, no compression needed")
                return True
                
        except Exception as e:
            logger.error(f"Error during compression: {e}")
            return True  # Continue even if compression fails

    def download_backup(self):
        """Download the backup file to local repository"""
        try:
            local_backup_dir = Path(__file__).parent
            local_backup_path = local_backup_dir / self.backup_filename
            
            logger.info(f"Downloading backup file to: {local_backup_path}")
            
            # Download the file
            self.sftp_client.get(self.backup_path, str(local_backup_path))
            
            # Verify download
            if local_backup_path.exists():
                local_size = local_backup_path.stat().st_size
                logger.info(f"Download successful: {local_size / (1024*1024):.2f}MB")
                
                # Clean up remote backup file
                stdin, stdout, stderr = self.ssh_client.exec_command(f"rm -f '{self.backup_path}'")
                logger.info("Remote backup file cleaned up")
                
                return str(local_backup_path)
            else:
                logger.error("Downloaded file not found locally")
                return None
                
        except Exception as e:
            logger.error(f"Failed to download backup file: {e}")
            return None

    def verify_backup_integrity(self, local_backup_path):
        """Verify the integrity of the backup file"""
        try:
            logger.info("Verifying backup file integrity...")
            
            backup_path = Path(local_backup_path)
            
            # Check file size
            file_size = backup_path.stat().st_size
            if file_size == 0:
                logger.error("Backup file is empty")
                return False
            
            # Check if file is compressed
            is_compressed = backup_path.suffix == '.gz'
            
            if is_compressed:
                # Verify gzip integrity
                try:
                    with gzip.open(backup_path, 'rt') as f:
                        # Read first few lines to verify it's a valid SQL dump
                        first_lines = []
                        for i, line in enumerate(f):
                            first_lines.append(line.strip())
                            if i >= 10:  # Read first 10 lines
                                break
                except Exception as e:
                    logger.error(f"Compressed file appears corrupted: {e}")
                    return False
            else:
                # Read first few lines directly
                with open(backup_path, 'r', encoding='utf-8') as f:
                    first_lines = [f.readline().strip() for _ in range(10)]
            
            # Verify SQL dump structure
            sql_indicators = [
                'mysqldump',
                'MariaDB dump',
                'CREATE DATABASE',
                'CREATE TABLE',
                'Table structure for table',
                'INSERT INTO',
                '-- MySQL dump',
                '-- MariaDB dump',
                '-- Host:',
                '-- Database:'
            ]
            
            content_text = ' '.join(first_lines)
            found_indicators = [indicator for indicator in sql_indicators if indicator in content_text]
            
            if len(found_indicators) >= 2:
                logger.info(f"Backup verification successful:")
                logger.info(f"  File size: {file_size / (1024*1024):.2f}MB")
                logger.info(f"  Compressed: {'Yes' if is_compressed else 'No'}")
                logger.info(f"  SQL indicators found: {found_indicators}")
                logger.info(f"  First few lines contain expected SQL dump headers")
                return True
            else:
                logger.error(f"Backup file does not appear to be a valid SQL dump")
                logger.error(f"Expected SQL indicators, found: {found_indicators}")
                return False
                
        except Exception as e:
            logger.error(f"Failed to verify backup integrity: {e}")
            return False

    def cleanup(self):
        """Close SSH connections"""
        try:
            if self.sftp_client:
                self.sftp_client.close()
            if self.ssh_client:
                self.ssh_client.close()
            logger.info("SSH connections closed")
        except Exception as e:
            logger.warning(f"Error during cleanup: {e}")

    def run_backup(self):
        """Execute the complete backup process"""
        logger.info("=" * 60)
        logger.info("PRODUCTION DATABASE BACKUP - STARTING")
        logger.info("=" * 60)
        
        try:
            # Step 1: Connect to SSH
            if not self.connect_ssh():
                return False
            
            # Step 2: Extract database credentials
            if not self.extract_database_credentials():
                return False
            
            # Step 3: Create database dump
            if not self.create_database_dump():
                return False
            
            # Step 4: Compress if needed
            if not self.compress_backup():
                return False
            
            # Step 5: Download backup
            local_backup_path = self.download_backup()
            if not local_backup_path:
                return False
            
            # Step 6: Verify integrity
            if not self.verify_backup_integrity(local_backup_path):
                return False
            
            logger.info("=" * 60)
            logger.info("PRODUCTION DATABASE BACKUP - COMPLETED SUCCESSFULLY")
            logger.info("=" * 60)
            logger.info(f"Backup file: {local_backup_path}")
            logger.info(f"Database: {self.db_credentials['database']}")
            logger.info(f"Timestamp: {self.timestamp}")
            logger.info("")
            logger.info("RESTORE INSTRUCTIONS:")
            logger.info("To restore this backup:")
            if local_backup_path.endswith('.gz'):
                logger.info(f"  gunzip {os.path.basename(local_backup_path)}")
                logger.info(f"  mysql -u USERNAME -p DATABASE_NAME < {os.path.basename(local_backup_path)[:-3]}")
            else:
                logger.info(f"  mysql -u USERNAME -p DATABASE_NAME < {os.path.basename(local_backup_path)}")
            logger.info("")
            logger.info("BACKUP IS READY FOR AUTOSAVE SYSTEM CHANGES")
            
            return True
            
        except Exception as e:
            logger.error(f"Backup process failed: {e}")
            return False
        finally:
            self.cleanup()

def main():
    """Main entry point"""
    if not os.getenv('SSH_PASS'):
        print("Error: SSH_PASS environment variable not set")
        print("Please create a .env file with SSH credentials")
        return 1
    
    backup = ProductionDatabaseBackup()
    success = backup.run_backup()
    
    return 0 if success else 1

if __name__ == "__main__":
    sys.exit(main())