#!/usr/bin/env python3
"""
SSH Agent - A specialized tool for remote server operations
Handles SSH connections, git operations, database queries, file operations, and system commands
"""

import paramiko
import socket
import time
import os
import sys
import logging
from typing import Optional, Dict, Any, Tuple, List
from datetime import datetime
import json
import re
import ast
from pathlib import Path
from dotenv import load_dotenv

load_dotenv()



class SSHAgent:
    """A comprehensive SSH agent for remote server operations"""
    
    def __init__(self, host: str = "mi3-cl9-its2.a2hosting.com", username: str = os.getenv("SSH_USER"), password: str = os.getenv("SSH_PASS"), port: int = 7822):
        self.host = host
        self.username = username
        self.password = password
        self.port = port
        self.client: Optional[paramiko.SSHClient] = None
        self.sftp: Optional[paramiko.SFTPClient] = None
        self.connected = False
        self.config_cache: Dict[str, Dict[str, Any]] = {}  # Cache for parsed configs
        
        # Setup logging
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(levelname)s - %(message)s',
            handlers=[
                logging.StreamHandler(sys.stdout)
            ]
        )
        self.logger = logging.getLogger(__name__)
    
    def connect(self) -> bool:
        """Establish SSH connection to the remote server"""
        try:
            self.logger.info(f"Connecting to {self.username}@{self.host}:{self.port}")
            
            self.client = paramiko.SSHClient()
            self.client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            self.client.connect(
                hostname=self.host,
                port=self.port,
                username=self.username,
                password=self.password,
                timeout=30
            )
            
            self.sftp = self.client.open_sftp()
            self.connected = True
            self.logger.info("SSH connection established successfully")
            return True
            
        except paramiko.AuthenticationException:
            self.logger.error("Authentication failed - check username/password")
            return False
        except paramiko.SSHException as e:
            self.logger.error(f"SSH connection error: {e}")
            return False
        except socket.timeout:
            self.logger.error("Connection timeout - check host and port")
            return False
        except Exception as e:
            self.logger.error(f"Unexpected error connecting: {e}")
            return False
    
    def disconnect(self):
        """Close SSH and SFTP connections"""
        if self.sftp:
            self.sftp.close()
        if self.client:
            self.client.close()
        self.connected = False
        self.logger.info("SSH connection closed")
    
    def ensure_connected(self) -> bool:
        """Ensure we have an active connection, reconnect if needed"""
        if not self.connected or not self.client:
            return self.connect()
        
        try:
            # Test connection with a simple command
            self.client.exec_command('echo "test"', timeout=5)
            return True
        except:
            self.logger.warning("Connection lost, attempting to reconnect...")
            return self.connect()
    
    def execute_command(self, command: str, timeout: int = 30, show_output: bool = True) -> Tuple[int, str, str]:
        """
        Execute a command on the remote server
        Returns: (exit_code, stdout, stderr)
        """
        if not self.ensure_connected():
            return -1, "", "SSH connection failed"
        
        try:
            if show_output:
                self.logger.info(f"Executing: {command}")
            
            stdin, stdout, stderr = self.client.exec_command(command, timeout=timeout)
            
            # Get the exit code
            exit_code = stdout.channel.recv_exit_status()
            
            # Read output
            stdout_data = stdout.read().decode('utf-8', errors='replace')
            stderr_data = stderr.read().decode('utf-8', errors='replace')
            
            if show_output and stdout_data:
                self.logger.info(f"Output:\n{stdout_data}")
            if show_output and stderr_data and exit_code != 0:
                self.logger.error(f"Error:\n{stderr_data}")
            
            return exit_code, stdout_data, stderr_data
            
        except socket.timeout:
            self.logger.error(f"Command timeout after {timeout} seconds")
            return -1, "", "Command timeout"
        except Exception as e:
            self.logger.error(f"Error executing command: {e}")
            return -1, "", str(e)
    
    # GIT OPERATIONS
    def git_pull(self, repo_path: str = ".", branch: str = "main") -> bool:
        """Pull latest changes from git repository"""
        self.logger.info(f"Pulling latest changes from git repository in {repo_path}")
        
        # Change to repository directory and pull
        commands = [
            f"cd {repo_path}",
            "git fetch origin",
            f"git pull origin {branch}"
        ]
        
        full_command = " && ".join(commands)
        exit_code, stdout, stderr = self.execute_command(full_command)
        
        if exit_code == 0:
            self.logger.info("Git pull completed successfully")
            return True
        else:
            self.logger.error(f"Git pull failed: {stderr}")
            return False
    
    def git_status(self, repo_path: str = ".") -> Dict[str, Any]:
        """Get git repository status"""
        self.logger.info(f"Checking git status in {repo_path}")
        
        commands = [
            f"cd {repo_path}",
            "git status --porcelain",
            "git branch --show-current",
            "git log -1 --format='%H|%an|%ad|%s' --date=short"
        ]
        
        results = {}
        
        # Get working tree status
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git status --porcelain")
        results['has_changes'] = len(stdout.strip()) > 0
        results['changes'] = stdout.strip().split('\n') if stdout.strip() else []
        
        # Get current branch
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git branch --show-current")
        results['current_branch'] = stdout.strip()
        
        # Get latest commit info
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git log -1 --format='%H|%an|%ad|%s' --date=short")
        if stdout.strip():
            commit_parts = stdout.strip().split('|')
            results['latest_commit'] = {
                'hash': commit_parts[0][:8],
                'author': commit_parts[1],
                'date': commit_parts[2],
                'message': commit_parts[3]
            }
        
        return results
    
    def git_log(self, repo_path: str = ".", count: int = 5) -> List[Dict[str, str]]:
        """Get recent git commits"""
        command = f"cd {repo_path} && git log -{count} --format='%H|%an|%ad|%s' --date=short"
        exit_code, stdout, stderr = self.execute_command(command, show_output=False)
        
        commits = []
        if stdout.strip():
            for line in stdout.strip().split('\n'):
                parts = line.split('|')
                if len(parts) >= 4:
                    commits.append({
                        'hash': parts[0][:8],
                        'author': parts[1],
                        'date': parts[2],
                        'message': parts[3]
                    })
        
        return commits
    
    def git_add(self, repo_path: str = ".", files: str = ".") -> bool:
        """Add files to git staging area"""
        self.logger.info(f"Adding files to git staging in {repo_path}: {files}")
        
        command = f"cd {repo_path} && git add {files}"
        exit_code, stdout, stderr = self.execute_command(command)
        
        if exit_code == 0:
            self.logger.info("Files added to staging successfully")
            return True
        else:
            self.logger.error(f"Git add failed: {stderr}")
            return False
    
    def git_commit(self, repo_path: str = ".", message: str = "Auto-commit from production", author_name: str = None, author_email: str = None) -> bool:
        """Commit staged changes to git repository"""
        self.logger.info(f"Committing changes in {repo_path}")
        
        # Set git user config if provided
        if author_name and author_email:
            config_commands = [
                f"cd {repo_path}",
                f"git config user.name '{author_name}'",
                f"git config user.email '{author_email}'"
            ]
            config_command = " && ".join(config_commands)
            self.execute_command(config_command, show_output=False)
        
        # Commit the changes
        escaped_message = message.replace("'", "'\"'\"'")
        command = f"cd {repo_path} && git commit -m '{escaped_message}'"
        exit_code, stdout, stderr = self.execute_command(command)
        
        if exit_code == 0:
            self.logger.info("Git commit completed successfully")
            return True
        else:
            self.logger.error(f"Git commit failed: {stderr}")
            return False
    
    def git_push(self, repo_path: str = ".", remote: str = "origin", branch: str = "main") -> bool:
        """Push commits to remote git repository"""
        self.logger.info(f"Pushing to {remote}/{branch} from {repo_path}")
        
        command = f"cd {repo_path} && git push {remote} {branch}"
        exit_code, stdout, stderr = self.execute_command(command)
        
        if exit_code == 0:
            self.logger.info("Git push completed successfully")
            return True
        else:
            self.logger.error(f"Git push failed: {stderr}")
            return False
    
    def git_status_detailed(self, repo_path: str = ".") -> Dict[str, Any]:
        """Get detailed git repository status including file changes"""
        self.logger.info(f"Getting detailed git status in {repo_path}")
        
        results = {}
        
        # Get working tree status with file details
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git status --porcelain", show_output=False)
        
        modified_files = []
        untracked_files = []
        deleted_files = []
        
        if stdout.strip():
            for line in stdout.strip().split('\n'):
                if line.startswith(' M '):
                    modified_files.append(line[3:])
                elif line.startswith('??'):
                    untracked_files.append(line[3:])
                elif line.startswith(' D '):
                    deleted_files.append(line[3:])
                elif line.startswith('M '):
                    modified_files.append(line[2:])
        
        results['modified_files'] = modified_files
        results['untracked_files'] = untracked_files
        results['deleted_files'] = deleted_files
        results['has_changes'] = len(modified_files) > 0 or len(untracked_files) > 0 or len(deleted_files) > 0
        
        # Get current branch
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git branch --show-current", show_output=False)
        results['current_branch'] = stdout.strip()
        
        # Get latest commit info
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git log -1 --format='%H|%an|%ad|%s' --date=short", show_output=False)
        if stdout.strip():
            commit_parts = stdout.strip().split('|')
            results['last_commit'] = {
                'hash': commit_parts[0][:8],
                'author': commit_parts[1],
                'date': commit_parts[2],
                'message': commit_parts[3]
            }
        
        # Check if ahead/behind remote
        exit_code, stdout, stderr = self.execute_command(f"cd {repo_path} && git status -b --porcelain", show_output=False)
        ahead_behind = ""
        if stdout.strip():
            first_line = stdout.strip().split('\n')[0]
            if '[ahead' in first_line or '[behind' in first_line:
                ahead_behind = first_line
        results['ahead_behind'] = ahead_behind
        
        return results
    
    # DATABASE OPERATIONS
    def mysql_query(self, query: str, database: str = None, user: str = "root", 
                   password: str = "", host: str = "localhost") -> Tuple[bool, str]:
        """Execute MySQL query on the remote server"""
        self.logger.info(f"Executing MySQL query: {query[:50]}...")
        
        # Escape the query for shell
        escaped_query = query.replace("'", "'\"'\"'")
        
        # Build MySQL command
        mysql_cmd_parts = ["mysql", "-h", host, "-u", user]
        if password:
            mysql_cmd_parts.extend(["-p" + password])
        if database:
            mysql_cmd_parts.append(database)
        
        mysql_cmd = " ".join(mysql_cmd_parts)
        full_command = f"echo '{escaped_query}' | {mysql_cmd}"
        
        exit_code, stdout, stderr = self.execute_command(full_command, show_output=False)
        
        if exit_code == 0:
            self.logger.info("MySQL query executed successfully")
            return True, stdout
        else:
            self.logger.error(f"MySQL query failed: {stderr}")
            return False, stderr
    
    def check_mysql_status(self, user: str = "root", password: str = "", 
                          host: str = "localhost") -> Dict[str, Any]:
        """Check MySQL server status and basic info"""
        self.logger.info("Checking MySQL server status")
        
        # Check if MySQL is running
        exit_code, stdout, stderr = self.execute_command("systemctl is-active mysql", show_output=False)
        mysql_running = exit_code == 0 and stdout.strip() == "active"
        
        result = {
            'running': mysql_running,
            'version': None,
            'databases': [],
            'status': {}
        }
        
        if mysql_running:
            # Get MySQL version
            success, output = self.mysql_query("SELECT VERSION();", user=user, password=password, host=host)
            if success:
                lines = output.strip().split('\n')
                for line in lines:
                    if not line.startswith('+') and not line.startswith('|') and line.strip():
                        if 'VERSION()' not in line:
                            result['version'] = line.strip()
                            break
            
            # Get list of databases
            success, output = self.mysql_query("SHOW DATABASES;", user=user, password=password, host=host)
            if success:
                databases = []
                lines = output.strip().split('\n')
                for line in lines:
                    if not line.startswith('+') and not line.startswith('|') and line.strip():
                        if 'Database' not in line:
                            databases.append(line.strip())
                result['databases'] = databases
        
        return result
    
    # CONFIGURATION PARSING OPERATIONS
    def parse_php_config_file(self, config_path: str, use_cache: bool = True) -> Dict[str, Any]:
        """
        Parse a PHP configuration file that returns an array
        Supports both local and remote files
        """
        # Check cache first
        if use_cache and config_path in self.config_cache:
            self.logger.info(f"Using cached config for: {config_path}")
            return self.config_cache[config_path]
        
        self.logger.info(f"Parsing PHP config file: {config_path}")
        
        # Read the file content
        success, content = self.read_file(config_path)
        if not success:
            self.logger.error(f"Failed to read config file: {content}")
            return {}
        
        # Parse PHP configuration
        config = self._parse_php_content(content)
        
        # Cache the result
        if use_cache:
            self.config_cache[config_path] = config
        
        return config
    
    def _parse_php_content(self, php_content: str) -> Dict[str, Any]:
        """Parse PHP content that returns an array configuration"""
        try:
            # Remove PHP opening/closing tags and comments
            content = re.sub(r'<\?php\s*', '', php_content)
            content = re.sub(r'\?>', '', content)
            content = re.sub(r'//.*?\n', '\n', content)
            content = re.sub(r'/\*.*?\*/', '', content, flags=re.DOTALL)
            content = re.sub(r'declare\s*\([^)]*\)\s*;', '', content)
            
            # Find the return array
            return_pattern = r'return\s*\[(.*?)\]\s*;'
            match = re.search(return_pattern, content, re.DOTALL)
            
            if not match:
                self.logger.error("No return array found in PHP config")
                return {}
            
            array_content = match.group(1)
            
            # Parse the PHP array into Python dict
            config = self._parse_php_array(array_content)
            return config
            
        except Exception as e:
            self.logger.error(f"Error parsing PHP config: {e}")
            return {}
    
    def _parse_php_array(self, array_content: str) -> Dict[str, Any]:
        """Parse PHP array content into Python dictionary"""
        result = {}
        
        # Handle nested arrays and key-value pairs
        # This is a simplified parser - in production you might want to use a proper PHP parser
        
        # Normalize whitespace but preserve structure
        content = array_content.strip()
        
        # Split by commas, but respect nested arrays and quotes
        items = self._split_php_array_items(content)
        
        for item in items:
            item = item.strip()
            if not item:
                continue
                
            # Handle key => value pairs
            if '=>' in item:
                # Split only on the first '=>' to handle nested arrays
                arrow_pos = item.find('=>')
                if arrow_pos > 0:
                    key_part = item[:arrow_pos].strip()
                    value_part = item[arrow_pos + 2:].strip()
                    
                    key = self._clean_php_value(key_part)
                    value = self._parse_php_value(value_part)
                    result[key] = value
        
        return result
    
    def _split_php_array_items(self, content: str) -> List[str]:
        """Split PHP array content by commas, respecting nested arrays and strings"""
        items = []
        current_item = ""
        bracket_depth = 0
        paren_depth = 0
        in_string = False
        string_char = None
        escape_next = False
        
        i = 0
        while i < len(content):
            char = content[i]
            
            # Handle escape sequences
            if escape_next:
                escape_next = False
                current_item += char
                i += 1
                continue
            
            if char == '\\' and in_string:
                escape_next = True
                current_item += char
                i += 1
                continue
            
            # Handle string literals
            if char in ['"', "'"] and not in_string:
                in_string = True
                string_char = char
            elif char == string_char and in_string:
                in_string = False
                string_char = None
            
            # Handle brackets and parentheses only when not in string
            elif not in_string:
                if char == '[':
                    bracket_depth += 1
                elif char == ']':
                    bracket_depth -= 1
                elif char == '(':
                    paren_depth += 1
                elif char == ')':
                    paren_depth -= 1
                elif char == ',' and bracket_depth == 0 and paren_depth == 0:
                    # Found a top-level comma separator
                    if current_item.strip():
                        items.append(current_item.strip())
                    current_item = ""
                    i += 1
                    continue
            
            current_item += char
            i += 1
        
        # Add the last item
        if current_item.strip():
            items.append(current_item.strip())
        
        return items
    
    def _parse_php_value(self, value: str) -> Any:
        """Parse a PHP value into appropriate Python type"""
        value = value.strip()
        
        # Handle arrays
        if value.startswith('[') and value.endswith(']'):
            array_content = value[1:-1].strip()
            if not array_content:
                return []
            
            # Check if it's an associative array or indexed array
            if '=>' in array_content:
                return self._parse_php_array(array_content)
            else:
                # Handle indexed arrays
                items = self._split_php_array_items(array_content)
                return [self._parse_php_value(item) for item in items if item.strip()]
        
        # Handle concatenated strings (basic support)
        if ' . ' in value and "'" in value:
            # Simple concatenation parsing - join string parts
            parts = value.split(' . ')
            result = ""
            for part in parts:
                part = part.strip()
                if (part.startswith("'") and part.endswith("'")) or (part.startswith('"') and part.endswith('"')):
                    result += part[1:-1]
                else:
                    result += str(part)  # For variables or constants
            return result
        
        # Handle strings
        if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
            return value[1:-1].replace('\\"', '"').replace("\\'", "'")
        
        # Handle booleans
        if value.lower() == 'true':
            return True
        elif value.lower() == 'false':
            return False
        
        # Handle null
        if value.lower() == 'null':
            return None
        
        # Handle numbers
        try:
            if '.' in value:
                return float(value)
            else:
                return int(value)
        except ValueError:
            pass
        
        # Handle PHP constants and variables (basic support)
        if value.startswith('PDO::') or value.startswith('__DIR__') or value.startswith('$'):
            # For PHP constants/variables, return as string for now
            return value
        
        # Default to string
        return self._clean_php_value(value)
    
    def _clean_php_value(self, value: str) -> str:
        """Clean PHP value by removing quotes and extra whitespace"""
        value = value.strip()
        if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
            value = value[1:-1]
        return value
    
    def get_database_config(self, config_path: str) -> Dict[str, Any]:
        """Extract database configuration from a PHP config file"""
        self.logger.info(f"Extracting database config from: {config_path}")
        
        config = self.parse_php_config_file(config_path)
        
        if 'database' in config:
            db_config = config['database']
            self.logger.info("Database configuration found")
            return {
                'host': db_config.get('host', 'localhost'),
                'database': db_config.get('dbname', ''),
                'username': db_config.get('username', ''),
                'password': db_config.get('password', ''),
                'charset': db_config.get('charset', 'utf8mb4'),
                'port': db_config.get('port', 3306)
            }
        else:
            self.logger.warning("No database configuration found in config file")
            return {}
    
    def parse_config_file(self, config_path: str) -> Dict[str, Any]:
        """
        Auto-detect and parse configuration file based on extension
        Supports PHP, JSON, and environment files
        """
        self.logger.info(f"Auto-parsing config file: {config_path}")
        
        file_ext = Path(config_path).suffix.lower()
        
        if file_ext == '.php':
            return self.parse_php_config_file(config_path)
        elif file_ext == '.json':
            return self.parse_json_config_file(config_path)
        elif file_ext in ['.env', '.environment']:
            return self.parse_env_config_file(config_path)
        else:
            # Try to detect format by content
            success, content = self.read_file(config_path)
            if success:
                content_lower = content.strip().lower()
                if content_lower.startswith('<?php') or 'return [' in content_lower:
                    return self._parse_php_content(content)
                elif content_lower.startswith('{'):
                    return self.parse_json_content(content)
                elif '=' in content and '\n' in content:
                    return self.parse_env_content(content)
            
            self.logger.error(f"Unable to determine config file format: {config_path}")
            return {}
    
    def parse_json_config_file(self, config_path: str) -> Dict[str, Any]:
        """Parse JSON configuration file"""
        success, content = self.read_file(config_path)
        if not success:
            return {}
        
        return self.parse_json_content(content)
    
    def parse_json_content(self, json_content: str) -> Dict[str, Any]:
        """Parse JSON content"""
        try:
            return json.loads(json_content)
        except json.JSONDecodeError as e:
            self.logger.error(f"Error parsing JSON config: {e}")
            return {}
    
    def parse_env_config_file(self, config_path: str) -> Dict[str, Any]:
        """Parse environment (.env) configuration file"""
        success, content = self.read_file(config_path)
        if not success:
            return {}
        
        return self.parse_env_content(content)
    
    def parse_env_content(self, env_content: str) -> Dict[str, Any]:
        """Parse environment file content"""
        config = {}
        
        for line in env_content.split('\n'):
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            
            if '=' in line:
                key, value = line.split('=', 1)
                key = key.strip()
                value = value.strip()
                
                # Remove quotes
                if (value.startswith('"') and value.endswith('"')) or (value.startswith("'") and value.endswith("'")):
                    value = value[1:-1]
                
                config[key] = value
        
        return config
    
    def connect_to_database_from_config(self, config_path: str) -> Tuple[bool, Dict[str, Any]]:
        """
        Automatically connect to database using configuration from server config file
        Returns: (success, database_config_used)
        """
        self.logger.info(f"Connecting to database using config from: {config_path}")
        
        db_config = self.get_database_config(config_path)
        
        if not db_config or not db_config.get('host'):
            self.logger.error("No valid database configuration found")
            return False, {}
        
        # Test the connection by checking MySQL status
        mysql_status = self.check_mysql_status(
            user=db_config['username'],
            password=db_config['password'],
            host=db_config['host']
        )
        
        success = mysql_status.get('running', False)
        
        if success:
            self.logger.info(f"Successfully connected to database: {db_config['database']}")
        else:
            self.logger.error("Failed to connect to database with config credentials")
        
        return success, db_config
    
    def smart_mysql_query(self, query: str, config_path: str = None, 
                         database: str = None, **manual_credentials) -> Tuple[bool, str]:
        """
        Execute MySQL query using either config file or manual credentials
        Automatically extracts database credentials from server config if config_path provided
        """
        self.logger.info(f"Executing smart MySQL query: {query[:50]}...")
        
        if config_path:
            db_config = self.get_database_config(config_path)
            if db_config:
                return self.mysql_query(
                    query=query,
                    database=database or db_config.get('database'),
                    user=db_config.get('username'),
                    password=db_config.get('password'),
                    host=db_config.get('host')
                )
            else:
                self.logger.warning("Failed to extract database config, falling back to manual credentials")
        
        # Fall back to manual credentials
        return self.mysql_query(query, database, **manual_credentials)
    
    def detect_environment(self, app_path: str = ".") -> Dict[str, Any]:
        """
        Detect the environment (production/staging/development) and locate config files
        """
        self.logger.info(f"Detecting environment in: {app_path}")
        
        environment_info = {
            'environment': 'unknown',
            'config_files': [],
            'detected_paths': []
        }
        
        # Common config file paths to check
        config_paths = [
            f"{app_path}/config/config.php",
            f"{app_path}/config/database.php",
            f"{app_path}/config/app.php",
            f"{app_path}/.env",
            f"{app_path}/.env.local",
            f"{app_path}/.env.production",
            f"{app_path}/.env.staging",
            f"{app_path}/.env.development",
            f"{app_path}/application/config/config.php",
            f"{app_path}/wp-config.php",  # WordPress
            f"{app_path}/configuration.php"  # Joomla
        ]
        
        existing_configs = []
        for config_path in config_paths:
            # Check if file exists
            exit_code, stdout, stderr = self.execute_command(f"test -f '{config_path}' && echo 'exists'", show_output=False)
            if exit_code == 0 and 'exists' in stdout:
                existing_configs.append(config_path)
        
        environment_info['config_files'] = existing_configs
        environment_info['detected_paths'] = existing_configs
        
        # Try to detect environment from config content or file names
        for config_path in existing_configs:
            if 'production' in config_path or 'prod' in config_path:
                environment_info['environment'] = 'production'
                break
            elif 'staging' in config_path or 'stage' in config_path:
                environment_info['environment'] = 'staging'
                break
            elif 'development' in config_path or 'dev' in config_path:
                environment_info['environment'] = 'development'
                break
        
        # If no environment detected from filename, try to parse main config
        if environment_info['environment'] == 'unknown' and existing_configs:
            main_config = existing_configs[0]
            config = self.parse_config_file(main_config)
            
            # Look for environment indicators in config
            if 'app' in config:
                app_config = config['app']
                if app_config.get('debug', False):
                    environment_info['environment'] = 'development'
                elif 'production' in str(app_config.get('base_url', '')).lower():
                    environment_info['environment'] = 'production'
                else:
                    environment_info['environment'] = 'production'  # Default assumption
        
        self.logger.info(f"Detected environment: {environment_info['environment']}")
        self.logger.info(f"Found {len(existing_configs)} config files")
        
        return environment_info
    
    def clear_config_cache(self):
        """Clear the configuration cache"""
        self.config_cache.clear()
        self.logger.info("Configuration cache cleared")
    
    # FILE OPERATIONS
    def read_file(self, remote_path: str) -> Tuple[bool, str]:
        """Read a file from the remote server"""
        if not self.ensure_connected():
            return False, "SSH connection failed"
        
        try:
            self.logger.info(f"Reading file: {remote_path}")
            with self.sftp.open(remote_path, 'r') as remote_file:
                content = remote_file.read().decode('utf-8', errors='replace')
            return True, content
        except FileNotFoundError:
            self.logger.error(f"File not found: {remote_path}")
            return False, "File not found"
        except Exception as e:
            self.logger.error(f"Error reading file: {e}")
            return False, str(e)
    
    def write_file(self, remote_path: str, content: str, backup: bool = True) -> bool:
        """Write content to a file on the remote server"""
        if not self.ensure_connected():
            return False
        
        try:
            self.logger.info(f"Writing file: {remote_path}")
            
            # Create backup if requested and file exists
            if backup:
                try:
                    self.sftp.stat(remote_path)
                    backup_path = f"{remote_path}.backup.{int(time.time())}"
                    self.copy_file(remote_path, backup_path)
                    self.logger.info(f"Created backup: {backup_path}")
                except FileNotFoundError:
                    pass  # File doesn't exist, no backup needed
            
            with self.sftp.open(remote_path, 'w') as remote_file:
                remote_file.write(content)
            
            self.logger.info("File written successfully")
            return True
            
        except Exception as e:
            self.logger.error(f"Error writing file: {e}")
            return False
    
    def copy_file(self, source_path: str, dest_path: str) -> bool:
        """Copy a file on the remote server"""
        exit_code, stdout, stderr = self.execute_command(f"cp '{source_path}' '{dest_path}'")
        return exit_code == 0
    
    def list_directory(self, remote_path: str = ".") -> List[Dict[str, Any]]:
        """List directory contents with details"""
        if not self.ensure_connected():
            return []
        
        try:
            self.logger.info(f"Listing directory: {remote_path}")
            
            files = []
            for item in self.sftp.listdir_attr(remote_path):
                file_info = {
                    'name': item.filename,
                    'size': item.st_size,
                    'modified': datetime.fromtimestamp(item.st_mtime).isoformat(),
                    'permissions': oct(item.st_mode)[-3:],
                    'is_directory': item.st_mode & 0o040000 != 0
                }
                files.append(file_info)
            
            return sorted(files, key=lambda x: (not x['is_directory'], x['name']))
            
        except Exception as e:
            self.logger.error(f"Error listing directory: {e}")
            return []
    
    def check_file_permissions(self, remote_path: str) -> Dict[str, Any]:
        """Check file permissions and ownership"""
        exit_code, stdout, stderr = self.execute_command(f"ls -la '{remote_path}'", show_output=False)
        
        if exit_code == 0 and stdout.strip():
            parts = stdout.strip().split()
            if len(parts) >= 9:
                return {
                    'permissions': parts[0],
                    'links': parts[1],
                    'owner': parts[2],
                    'group': parts[3],
                    'size': parts[4],
                    'modified': ' '.join(parts[5:8]),
                    'name': ' '.join(parts[8:])
                }
        
        return {}
    
    # SYSTEM OPERATIONS
    def get_system_info(self) -> Dict[str, Any]:
        """Get basic system information"""
        self.logger.info("Gathering system information")
        
        info = {}
        
        # OS info
        exit_code, stdout, stderr = self.execute_command("uname -a", show_output=False)
        if exit_code == 0:
            info['os'] = stdout.strip()
        
        # Uptime
        exit_code, stdout, stderr = self.execute_command("uptime", show_output=False)
        if exit_code == 0:
            info['uptime'] = stdout.strip()
        
        # Disk usage
        exit_code, stdout, stderr = self.execute_command("df -h", show_output=False)
        if exit_code == 0:
            info['disk_usage'] = stdout.strip()
        
        # Memory usage
        exit_code, stdout, stderr = self.execute_command("free -h", show_output=False)
        if exit_code == 0:
            info['memory'] = stdout.strip()
        
        # Current directory
        exit_code, stdout, stderr = self.execute_command("pwd", show_output=False)
        if exit_code == 0:
            info['current_directory'] = stdout.strip()
        
        return info
    
    def check_service_status(self, service_name: str) -> Dict[str, str]:
        """Check the status of a system service"""
        self.logger.info(f"Checking status of service: {service_name}")
        
        exit_code, stdout, stderr = self.execute_command(f"systemctl status {service_name}", show_output=False)
        
        return {
            'service': service_name,
            'running': exit_code == 0,
            'status': stdout.strip() if stdout else stderr.strip()
        }
    
    def find_process(self, process_name: str) -> List[Dict[str, str]]:
        """Find running processes by name"""
        exit_code, stdout, stderr = self.execute_command(f"ps aux | grep '{process_name}' | grep -v grep", show_output=False)
        
        processes = []
        if stdout.strip():
            for line in stdout.strip().split('\n'):
                parts = line.split(None, 10)
                if len(parts) >= 11:
                    processes.append({
                        'user': parts[0],
                        'pid': parts[1],
                        'cpu': parts[2],
                        'memory': parts[3],
                        'command': parts[10]
                    })
        
        return processes
    
    # HIGH-LEVEL OPERATIONS
    def deploy_code(self, repo_path: str = ".", branch: str = "main", 
                   restart_services: List[str] = None) -> bool:
        """Complete code deployment: pull, update permissions, restart services"""
        self.logger.info("Starting code deployment process")
        
        success = True
        
        # Pull latest code
        if not self.git_pull(repo_path, branch):
            success = False
        
        # Update file permissions if needed
        if success:
            exit_code, stdout, stderr = self.execute_command(f"find {repo_path} -type f -name '*.php' -exec chmod 644 {{}} \\;")
            if exit_code != 0:
                self.logger.warning("Failed to update PHP file permissions")
        
        # Restart services
        if success and restart_services:
            for service in restart_services:
                exit_code, stdout, stderr = self.execute_command(f"sudo systemctl restart {service}")
                if exit_code != 0:
                    self.logger.error(f"Failed to restart service: {service}")
                    success = False
        
        if success:
            self.logger.info("Code deployment completed successfully")
        else:
            self.logger.error("Code deployment failed")
        
        return success
    
    def health_check(self, checks: Dict[str, Any] = None) -> Dict[str, Any]:
        """Perform comprehensive health check"""
        self.logger.info("Performing system health check")
        
        if checks is None:
            checks = {
                'mysql': {'user': 'root', 'password': ''},
                'services': ['apache2', 'mysql'],
                'disk_threshold': 90  # Percentage
            }
        
        health = {
            'timestamp': datetime.now().isoformat(),
            'overall_status': 'healthy',
            'checks': {}
        }
        
        # System info
        health['system'] = self.get_system_info()
        
        # MySQL check
        if 'mysql' in checks:
            mysql_config = checks['mysql']
            mysql_status = self.check_mysql_status(**mysql_config)
            health['checks']['mysql'] = mysql_status
            if not mysql_status['running']:
                health['overall_status'] = 'unhealthy'
        
        # Service checks
        if 'services' in checks:
            health['checks']['services'] = {}
            for service in checks['services']:
                service_status = self.check_service_status(service)
                health['checks']['services'][service] = service_status
                if not service_status['running']:
                    health['overall_status'] = 'unhealthy'
        
        # Disk usage check
        if 'disk_threshold' in checks:
            disk_info = health['system'].get('disk_usage', '')
            # Parse disk usage for root filesystem
            for line in disk_info.split('\n'):
                if line.strip().endswith('/'):
                    parts = line.split()
                    if len(parts) >= 5:
                        usage_percent = int(parts[4].rstrip('%'))
                        health['checks']['disk_usage'] = {
                            'usage_percent': usage_percent,
                            'threshold': checks['disk_threshold'],
                            'status': 'ok' if usage_percent < checks['disk_threshold'] else 'warning'
                        }
                        if usage_percent >= checks['disk_threshold']:
                            health['overall_status'] = 'warning'
                    break
        
        return health


def main():
    """Interactive SSH Agent CLI"""
    print("SSH Agent - Remote Server Management Tool")
    print("=" * 50)
    
    # Create agent and connect
    agent = SSHAgent()
    
    if not agent.connect():
        print("Failed to connect to server")
        return
    
    print("\nConnected successfully!")
    print("\nAvailable commands:")
    print("  git pull [path] [branch] - Pull latest code")
    print("  git status [path] - Check git status")
    print("  git status-detailed [path] - Detailed git status with files")
    print("  git log [path] [count] - Show recent commits")
    print("  git add [path] [files] - Add files to staging")
    print("  git commit [path] [message] - Commit staged changes")
    print("  git push [path] [remote] [branch] - Push commits to remote")
    print("  mysql query <query> [database] - Execute MySQL query")
    print("  mysql status - Check MySQL status")
    print("  mysql smart <query> <config_path> - Execute query using server config")
    print("  config parse <file> - Parse any config file (PHP/JSON/env)")
    print("  config db <file> - Extract database config from file")
    print("  config connect <file> - Test database connection using config")
    print("  config detect [path] - Detect environment and config files")
    print("  config cache clear - Clear configuration cache")
    print("  read <file> - Read file content")
    print("  write <file> - Write to file")
    print("  ls [path] - List directory")
    print("  perms <file> - Check file permissions")
    print("  system info - Get system information")
    print("  service <name> - Check service status")
    print("  process <name> - Find processes")
    print("  deploy [path] [branch] - Deploy code")
    print("  health - System health check")
    print("  cmd <command> - Execute raw command")
    print("  quit - Exit")
    
    while True:
        try:
            command = input("\n> ").strip()
            
            if not command:
                continue
            
            if command == "quit":
                break
            
            parts = command.split()
            cmd = parts[0]
            
            if cmd == "git":
                if len(parts) < 2:
                    print("Usage: git <pull|status|log> [args...]")
                    continue
                
                subcmd = parts[1]
                
                if subcmd == "pull":
                    path = parts[2] if len(parts) > 2 else "."
                    branch = parts[3] if len(parts) > 3 else "main"
                    agent.git_pull(path, branch)
                
                elif subcmd == "status":
                    path = parts[2] if len(parts) > 2 else "."
                    status = agent.git_status(path)
                    print(json.dumps(status, indent=2))
                
                elif subcmd == "log":
                    path = parts[2] if len(parts) > 2 else "."
                    count = int(parts[3]) if len(parts) > 3 else 5
                    commits = agent.git_log(path, count)
                    for commit in commits:
                        print(f"{commit['hash']} - {commit['date']} - {commit['author']}: {commit['message']}")
                
                elif subcmd == "status-detailed":
                    path = parts[2] if len(parts) > 2 else "."
                    status = agent.git_status_detailed(path)
                    print(json.dumps(status, indent=2))
                
                elif subcmd == "add":
                    path = parts[2] if len(parts) > 2 else "."
                    files = parts[3] if len(parts) > 3 else "."
                    success = agent.git_add(path, files)
                    print("Files added successfully" if success else "Failed to add files")
                
                elif subcmd == "commit":
                    path = parts[2] if len(parts) > 2 else "."
                    message = " ".join(parts[3:]) if len(parts) > 3 else "Auto-commit from SSH agent"
                    success = agent.git_commit(path, message)
                    print("Commit successful" if success else "Commit failed")
                
                elif subcmd == "push":
                    path = parts[2] if len(parts) > 2 else "."
                    remote = parts[3] if len(parts) > 3 else "origin"
                    branch = parts[4] if len(parts) > 4 else "main"
                    success = agent.git_push(path, remote, branch)
                    print("Push successful" if success else "Push failed")
            
            elif cmd == "mysql":
                if len(parts) < 2:
                    print("Usage: mysql <query|status> [args...]")
                    continue
                
                subcmd = parts[1]
                
                if subcmd == "query":
                    if len(parts) < 3:
                        print("Usage: mysql query <query> [database]")
                        continue
                    
                    query = " ".join(parts[2:])
                    database = None
                    
                    # Check if last part looks like a database name
                    if len(parts) > 3 and not query.lower().strip().endswith(';'):
                        database = parts[-1]
                        query = " ".join(parts[2:-1])
                    
                    success, result = agent.mysql_query(query, database)
                    print(result)
                
                elif subcmd == "status":
                    status = agent.check_mysql_status()
                    print(json.dumps(status, indent=2))
                
                elif subcmd == "smart":
                    if len(parts) < 4:
                        print("Usage: mysql smart <query> <config_path>")
                        continue
                    
                    query = parts[2]
                    config_path = parts[3]
                    success, result = agent.smart_mysql_query(query, config_path)
                    print(result)
            
            elif cmd == "config":
                if len(parts) < 2:
                    print("Usage: config <parse|db|connect|detect|cache> [args...]")
                    continue
                
                subcmd = parts[1]
                
                if subcmd == "parse":
                    if len(parts) < 3:
                        print("Usage: config parse <file>")
                        continue
                    
                    config = agent.parse_config_file(parts[2])
                    print(json.dumps(config, indent=2))
                
                elif subcmd == "db":
                    if len(parts) < 3:
                        print("Usage: config db <file>")
                        continue
                    
                    db_config = agent.get_database_config(parts[2])
                    print(json.dumps(db_config, indent=2))
                
                elif subcmd == "connect":
                    if len(parts) < 3:
                        print("Usage: config connect <file>")
                        continue
                    
                    success, db_config = agent.connect_to_database_from_config(parts[2])
                    print(f"Connection {'successful' if success else 'failed'}")
                    if db_config:
                        # Don't print password in full for security
                        safe_config = db_config.copy()
                        if 'password' in safe_config:
                            safe_config['password'] = '*' * len(safe_config['password'])
                        print(json.dumps(safe_config, indent=2))
                
                elif subcmd == "detect":
                    path = parts[2] if len(parts) > 2 else "."
                    env_info = agent.detect_environment(path)
                    print(json.dumps(env_info, indent=2))
                
                elif subcmd == "cache":
                    if len(parts) > 2 and parts[2] == "clear":
                        agent.clear_config_cache()
                        print("Configuration cache cleared")
                    else:
                        print("Usage: config cache clear")
            
            elif cmd == "read":
                if len(parts) < 2:
                    print("Usage: read <file>")
                    continue
                
                success, content = agent.read_file(parts[1])
                if success:
                    print(content)
                else:
                    print(f"Error: {content}")
            
            elif cmd == "write":
                if len(parts) < 2:
                    print("Usage: write <file>")
                    continue
                
                print("Enter content (Ctrl+D to finish):")
                content = sys.stdin.read()
                success = agent.write_file(parts[1], content)
                print("File written successfully" if success else "Failed to write file")
            
            elif cmd == "ls":
                path = parts[1] if len(parts) > 1 else "."
                files = agent.list_directory(path)
                for file_info in files:
                    print(f"{'d' if file_info['is_directory'] else '-'}{file_info['permissions']} "
                          f"{file_info['size']:>8} {file_info['modified']} {file_info['name']}")
            
            elif cmd == "perms":
                if len(parts) < 2:
                    print("Usage: perms <file>")
                    continue
                
                perms = agent.check_file_permissions(parts[1])
                print(json.dumps(perms, indent=2))
            
            elif cmd == "system":
                if len(parts) > 1 and parts[1] == "info":
                    info = agent.get_system_info()
                    print(json.dumps(info, indent=2))
            
            elif cmd == "service":
                if len(parts) < 2:
                    print("Usage: service <name>")
                    continue
                
                status = agent.check_service_status(parts[1])
                print(json.dumps(status, indent=2))
            
            elif cmd == "process":
                if len(parts) < 2:
                    print("Usage: process <name>")
                    continue
                
                processes = agent.find_process(parts[1])
                for proc in processes:
                    print(f"{proc['pid']:>8} {proc['user']:>10} {proc['cpu']:>6}% {proc['memory']:>6}% {proc['command']}")
            
            elif cmd == "deploy":
                path = parts[1] if len(parts) > 1 else "."
                branch = parts[2] if len(parts) > 2 else "main"
                success = agent.deploy_code(path, branch)
                print("Deployment successful" if success else "Deployment failed")
            
            elif cmd == "health":
                health = agent.health_check()
                print(json.dumps(health, indent=2))
            
            elif cmd == "cmd":
                if len(parts) < 2:
                    print("Usage: cmd <command>")
                    continue
                
                raw_command = " ".join(parts[1:])
                exit_code, stdout, stderr = agent.execute_command(raw_command)
                print(f"Exit code: {exit_code}")
                if stdout:
                    print(f"Output:\n{stdout}")
                if stderr:
                    print(f"Error:\n{stderr}")
            
            else:
                print(f"Unknown command: {cmd}")
        
        except KeyboardInterrupt:
            print("\nInterrupted")
            break
        except Exception as e:
            print(f"Error: {e}")
    
    agent.disconnect()
    print("Goodbye!")


if __name__ == "__main__":
    main()