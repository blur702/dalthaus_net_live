#!/usr/bin/env python3
"""
Remote File Agent Client
Interacts with the PHP agent on shared hosting
"""

import requests
import json
import sys
from datetime import datetime
from typing import Optional, Dict, Any

class RemoteFileAgent:
    def __init__(self, base_url: str, token: Optional[str] = None):
        """
        Initialize the remote file agent client
        
        Args:
            base_url: URL to the remote-file-agent.php (e.g., https://dalthaus.net/remote-file-agent.php)
            token: Authentication token (defaults to daily token)
        """
        self.base_url = base_url
        self.token = token or f"agent-{datetime.now().strftime('%Y%m%d')}"
        self.session = requests.Session()
        self.session.headers.update({
            'X-Auth-Token': self.token
        })
    
    def request(self, action: str, **params) -> Dict[str, Any]:
        """Make a request to the agent"""
        data = {'action': action, **params}
        
        try:
            response = self.session.post(self.base_url, data=data)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            return {'success': False, 'error': str(e)}
        except json.JSONDecodeError:
            return {'success': False, 'error': 'Invalid JSON response', 'content': response.text}
    
    def read(self, path: str) -> Optional[str]:
        """Read a file from the remote server"""
        result = self.request('read', path=path)
        if result.get('success'):
            return result.get('content')
        else:
            print(f"Error reading {path}: {result.get('error')}")
            return None
    
    def write(self, path: str, content: str) -> bool:
        """Write content to a file on the remote server"""
        result = self.request('write', path=path, content=content)
        if result.get('success'):
            print(f"Successfully wrote {result.get('bytes')} bytes to {path}")
            return True
        else:
            print(f"Error writing {path}: {result.get('error')}")
            return False
    
    def delete(self, path: str) -> bool:
        """Delete a file or directory from the remote server"""
        result = self.request('delete', path=path)
        if result.get('success'):
            print(f"Successfully deleted {path}")
            return True
        else:
            print(f"Error deleting {path}: {result.get('error')}")
            return False
    
    def list_files(self, path: str = '.') -> Optional[list]:
        """List files in a directory"""
        result = self.request('list', path=path)
        if result.get('success'):
            return result.get('files', [])
        else:
            print(f"Error listing {path}: {result.get('error')}")
            return None
    
    def exists(self, path: str) -> bool:
        """Check if a file or directory exists"""
        result = self.request('exists', path=path)
        return result.get('success') and result.get('exists', False)
    
    def mkdir(self, path: str) -> bool:
        """Create a directory"""
        result = self.request('mkdir', path=path)
        if result.get('success'):
            print(f"Successfully created directory {path}")
            return True
        else:
            print(f"Error creating directory {path}: {result.get('error')}")
            return False
    
    def chmod(self, path: str, mode: str) -> bool:
        """Change file permissions"""
        result = self.request('chmod', path=path, mode=mode)
        if result.get('success'):
            print(f"Successfully changed permissions of {path} to {mode}")
            return True
        else:
            print(f"Error changing permissions: {result.get('error')}")
            return False
    
    def get_info(self) -> Dict[str, Any]:
        """Get server information"""
        return self.request('info')


# Example usage and testing
if __name__ == '__main__':
    # Initialize the agent
    agent_url = 'https://dalthaus.net/remote-file-agent.php'
    agent = RemoteFileAgent(agent_url)
    
    # Get server info
    print("Getting server info...")
    info = agent.get_info()
    if info.get('success'):
        print(json.dumps(info.get('server'), indent=2))
    
    # Example operations
    if len(sys.argv) > 1:
        command = sys.argv[1]
        
        if command == 'read' and len(sys.argv) > 2:
            content = agent.read(sys.argv[2])
            if content:
                print(content)
        
        elif command == 'write' and len(sys.argv) > 3:
            agent.write(sys.argv[2], sys.argv[3])
        
        elif command == 'list' and len(sys.argv) > 2:
            files = agent.list_files(sys.argv[2])
            if files:
                for f in files:
                    print(f"{f['type']:10} {f['name']}")
        
        elif command == 'delete' and len(sys.argv) > 2:
            agent.delete(sys.argv[2])
        
        elif command == 'exists' and len(sys.argv) > 2:
            exists = agent.exists(sys.argv[2])
            print(f"File exists: {exists}")
        
        else:
            print("Usage:")
            print("  python remote-client.py read <path>")
            print("  python remote-client.py write <path> <content>")
            print("  python remote-client.py list <path>")
            print("  python remote-client.py delete <path>")
            print("  python remote-client.py exists <path>")
    else:
        # Run test operations
        print("\nTesting basic operations...")
        
        # Test write
        test_file = 'test-agent.txt'
        test_content = f"Test from agent at {datetime.now()}"
        if agent.write(test_file, test_content):
            print(f"✓ Write test passed")
        
        # Test read
        content = agent.read(test_file)
        if content == test_content:
            print(f"✓ Read test passed")
        
        # Test exists
        if agent.exists(test_file):
            print(f"✓ Exists test passed")
        
        # Test list
        files = agent.list_files('.')
        if files and any(f['name'] == test_file for f in files):
            print(f"✓ List test passed")
        
        # Test delete
        if agent.delete(test_file):
            print(f"✓ Delete test passed")