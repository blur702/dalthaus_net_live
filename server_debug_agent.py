#!/usr/bin/env python3
"""
Server-Side Debugging Agent
Comprehensive debugging tool for server-side issues
"""

import sys
import os
import json
from datetime import datetime
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ssh_agent import SSHAgent

class ServerDebugAgent:
    """Server-side debugging and testing agent"""

    def __init__(self):
        # Load SSH configuration
        try:
            from ssh_config import SSH_CONFIG
            self.host = SSH_CONFIG["host"]
            self.username = SSH_CONFIG["username"]
            self.password = SSH_CONFIG["password"]
            self.port = SSH_CONFIG["port"]
            self.web_root = SSH_CONFIG["web_root"]
        except ImportError:
            print("[ERROR] ssh_config.py not found!")
            sys.exit(1)

        self.agent = None
        self.db_config = {
            'host': 'localhost',
            'dbname': 'dalthaus_maincms',
            'username': 'dalthaus_maincms',
            'password': 'f4!,Wpds=w6*=~+1'
        }

    def connect(self):
        """Connect to the server"""
        print(f"[DEBUG] Connecting to {self.host}...")
        self.agent = SSHAgent(self.host, self.username, self.password, self.port)

        if self.agent.connect():
            print("[SUCCESS] Connected to server successfully!")
            return True
        else:
            print("[ERROR] Failed to connect to server")
            return False

    def execute_command(self, command, description=""):
        """Execute a command on the server"""
        if description:
            print(f"[EXEC] {description}")

        exit_code, output, error = self.agent.execute_command(command)

        if exit_code == 0:
            return output
        else:
            print(f"[ERROR] Command failed: {error}")
            return None

    def setup_testing_environment(self):
        """Set up the testing environment on the server"""
        print("\n=== SETTING UP TESTING ENVIRONMENT ===")

        # Create tests directory
        self.execute_command(f"mkdir -p {self.web_root}/tests/e2e", "Creating tests directory")
        self.execute_command(f"mkdir -p {self.web_root}/tests/debug", "Creating debug directory")
        self.execute_command(f"mkdir -p {self.web_root}/tests/logs", "Creating logs directory")

        # Check if Node.js is available
        node_version = self.execute_command("node --version 2>/dev/null || echo 'NOT_INSTALLED'")
        print(f"[INFO] Node.js version: {node_version}")

        if "NOT_INSTALLED" in str(node_version):
            print("[INFO] Installing Node.js...")
            # Install Node.js via NodeSource
            self.execute_command("curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -", "Setting up NodeSource repository")
            self.execute_command("sudo yum install nodejs -y", "Installing Node.js")

        # Check npm
        npm_version = self.execute_command("npm --version 2>/dev/null || echo 'NOT_INSTALLED'")
        print(f"[INFO] npm version: {npm_version}")

        return True

    def install_playwright(self):
        """Install Playwright on the server"""
        print("\n=== INSTALLING PLAYWRIGHT ===")

        # Create package.json for the tests
        package_json = {
            "name": "dalthaus-server-tests",
            "version": "1.0.0",
            "description": "Server-side testing for dalthaus.net",
            "scripts": {
                "test": "playwright test",
                "test:headed": "playwright test --headed",
                "test:debug": "playwright test --debug"
            },
            "devDependencies": {
                "@playwright/test": "^1.40.0"
            }
        }

        # Write package.json
        package_json_content = json.dumps(package_json, indent=2)
        self.execute_command(f"cat > {self.web_root}/tests/package.json << 'EOF'\n{package_json_content}\nEOF", "Creating package.json")

        # Install Playwright
        self.execute_command(f"cd {self.web_root}/tests && npm install", "Installing Playwright")
        self.execute_command(f"cd {self.web_root}/tests && npx playwright install", "Installing browser binaries")

        return True

    def create_debug_endpoints(self):
        """Create debug endpoints for testing"""
        print("\n=== CREATING DEBUG ENDPOINTS ===")

        debug_endpoint = '''<?php
/**
 * Debug endpoint for testing authentication and sessions
 */

// Start session
session_start();

// Set content type
header('Content-Type: application/json');

// Debug information
$debug_info = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_status' => session_status(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>'''

        self.execute_command(f"cat > {self.web_root}/debug_session.php << 'EOF'\n{debug_endpoint}\nEOF", "Creating debug session endpoint")

        # Create authentication test endpoint
        auth_test_endpoint = '''<?php
/**
 * Authentication test endpoint
 */

require_once __DIR__ . '/bootstrap.php';

use CMS\\Controllers\\BaseController;

class DebugController extends BaseController {
    public function testAuth() {
        $this->renderJson([
            'authenticated' => $this->isAuthenticated(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'is_admin' => $_SESSION['is_admin'] ?? null,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

$controller = new DebugController();
$controller->testAuth();
?>'''

        self.execute_command(f"cat > {self.web_root}/debug_auth.php << 'EOF'\n{auth_test_endpoint}\nEOF", "Creating debug auth endpoint")

        return True

    def create_server_test_suite(self):
        """Create comprehensive test suite for server-side testing"""
        print("\n=== CREATING SERVER TEST SUITE ===")

        # Playwright config
        playwright_config = '''import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html', { outputFolder: './logs/html-report' }],
    ['json', { outputFile: './logs/test-results.json' }],
    ['list']
  ],
  use: {
    baseURL: 'https://dalthaus.net',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ],
  outputDir: './logs/test-results',
});'''

        self.execute_command(f"cat > {self.web_root}/tests/playwright.config.ts << 'EOF'\n{playwright_config}\nEOF", "Creating Playwright config")

        # Comprehensive reorder test
        reorder_test = '''import { test, expect } from '@playwright/test';

test.describe('Complete Reordering Functionality Tests', () => {
  const credentials = {
    username: 'kevin',
    password: '(130Bpm)'
  };

  test.beforeEach(async ({ page }) => {
    // Enable request/response logging
    page.on('request', request => console.log('→', request.method(), request.url()));
    page.on('response', response => console.log('←', response.status(), response.url()));

    // Login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('/admin/dashboard');
  });

  test('Debug session information', async ({ page }) => {
    console.log('=== SESSION DEBUG TEST ===');

    // Get session debug info
    const response = await page.goto('/debug_session.php');
    const debugInfo = await response.json();
    console.log('Session Debug Info:', JSON.stringify(debugInfo, null, 2));

    expect(debugInfo.session_id).toBeTruthy();
    expect(debugInfo.session_data.user_id).toBeTruthy();
  });

  test('Debug authentication status', async ({ page }) => {
    console.log('=== AUTHENTICATION DEBUG TEST ===');

    // Test auth endpoint
    const response = await page.goto('/debug_auth.php');
    const authInfo = await response.json();
    console.log('Auth Debug Info:', JSON.stringify(authInfo, null, 2));

    expect(authInfo.authenticated).toBe(true);
    expect(authInfo.user_id).toBeTruthy();
  });

  test('Test pages reorder functionality', async ({ page }) => {
    console.log('=== PAGES REORDER TEST ===');

    // Navigate to pages reorder
    const response = await page.goto('/admin/pages/reorder');
    console.log('Pages reorder response status:', response.status());

    expect(response.status()).toBe(200);
    await expect(page.locator('h2')).toContainText('Reorder Pages');

    // Check for Sortable.js
    const sortableExists = await page.evaluate(() => typeof window.Sortable !== 'undefined');
    console.log('Sortable.js loaded:', sortableExists);
    expect(sortableExists).toBe(true);
  });

  test('Test content reorder functionality', async ({ page }) => {
    console.log('=== CONTENT REORDER TEST ===');

    // Navigate to content reorder
    const response = await page.goto('/admin/content/reorder');
    console.log('Content reorder response status:', response.status());
    console.log('Final URL:', page.url());

    if (response.status() === 302) {
      console.log('REDIRECT DETECTED - Investigating...');

      // Check if redirected to login
      if (page.url().includes('/admin/login')) {
        console.log('ERROR: Redirected to login page');

        // Get session info after redirect
        const sessionResponse = await page.goto('/debug_session.php');
        const sessionInfo = await sessionResponse.json();
        console.log('Session after redirect:', JSON.stringify(sessionInfo, null, 2));

        // Try direct authentication test
        const authResponse = await page.goto('/debug_auth.php');
        const authInfo = await authResponse.json();
        console.log('Auth after redirect:', JSON.stringify(authInfo, null, 2));
      }
    } else {
      expect(response.status()).toBe(200);
      await expect(page.locator('h2')).toContainText('Reorder Content');
    }
  });

  test('Test content reorder with fresh login', async ({ page }) => {
    console.log('=== CONTENT REORDER WITH FRESH LOGIN ===');

    // Clear all storage and login fresh
    await page.context().clearCookies();
    await page.evaluate(() => {
      localStorage.clear();
      sessionStorage.clear();
    });

    // Fresh login
    await page.goto('/admin/login');
    await page.fill('input[name="username"]', credentials.username);
    await page.fill('input[name="password"]', credentials.password);
    await page.click('button[type="submit"]');
    await page.waitForURL('/admin/dashboard');

    // Get session info
    const sessionResponse = await page.goto('/debug_session.php');
    const sessionInfo = await sessionResponse.json();
    console.log('Fresh session info:', JSON.stringify(sessionInfo, null, 2));

    // Now try content reorder
    const response = await page.goto('/admin/content/reorder');
    console.log('Content reorder with fresh session:', response.status());
    console.log('Final URL:', page.url());

    if (response.status() !== 200) {
      console.log('STILL FAILING - This indicates a deeper issue');
    }
  });

  test('Compare working vs failing routes', async ({ page }) => {
    console.log('=== ROUTE COMPARISON TEST ===');

    // Test working route (pages)
    const pagesResponse = await page.goto('/admin/pages/reorder');
    console.log('Pages reorder status:', pagesResponse.status());

    // Test failing route (content)
    const contentResponse = await page.goto('/admin/content/reorder');
    console.log('Content reorder status:', contentResponse.status());

    // Test other content routes
    const contentIndexResponse = await page.goto('/admin/content');
    console.log('Content index status:', contentIndexResponse.status());

    const contentCreateResponse = await page.goto('/admin/content/create');
    console.log('Content create status:', contentCreateResponse.status());

    // Get current session state
    const sessionResponse = await page.goto('/debug_session.php');
    const sessionInfo = await sessionResponse.json();
    console.log('Session during comparison:', JSON.stringify(sessionInfo, null, 2));
  });
});'''

        self.execute_command(f"cat > {self.web_root}/tests/e2e/reorder-comprehensive.spec.ts << 'EOF'\n{reorder_test}\nEOF", "Creating comprehensive reorder test")

        return True

    def debug_authentication_flow(self):
        """Debug the authentication flow in detail"""
        print("\n=== DEBUGGING AUTHENTICATION FLOW ===")

        # Check current session configuration
        session_config = self.execute_command("grep -r 'session' /home/dalthaus/public_html/config/ || echo 'No session config found'")
        print(f"Session config: {session_config}")

        # Check session files
        session_path = self.execute_command("php -r \"echo session_save_path();\"")
        print(f"Session save path: {session_path}")

        # Check recent session files
        recent_sessions = self.execute_command("find /tmp -name 'sess_*' -mtime -1 -ls 2>/dev/null | head -5 || echo 'No recent sessions'")
        print(f"Recent sessions: {recent_sessions}")

        # Check error logs for authentication issues
        auth_errors = self.execute_command("grep -i 'auth\\|session\\|login' /home/dalthaus/public_html/logs/error.log | tail -10")
        print(f"Recent auth errors: {auth_errors}")

        return True

    def run_comprehensive_tests(self):
        """Run the complete test suite"""
        print("\n=== RUNNING COMPREHENSIVE TESTS ===")

        # Run Playwright tests
        test_output = self.execute_command(f"cd {self.web_root}/tests && npm test", "Running Playwright tests")
        print(f"Test output: {test_output}")

        # Get test results
        results = self.execute_command(f"cat {self.web_root}/tests/logs/test-results.json 2>/dev/null || echo 'No results file'")
        print(f"Test results: {results}")

        return True

    def analyze_content_controller(self):
        """Analyze the Content controller for issues"""
        print("\n=== ANALYZING CONTENT CONTROLLER ===")

        # Check if Content controller file exists and is readable
        content_controller_check = self.execute_command(f"ls -la {self.web_root}/src/Controllers/Admin/Content.php")
        print(f"Content controller file: {content_controller_check}")

        # Check the reorder method specifically
        reorder_method = self.execute_command(f"grep -A 20 'public function reorder' {self.web_root}/src/Controllers/Admin/Content.php")
        print(f"Reorder method: {reorder_method}")

        # Compare with Pages controller
        pages_reorder = self.execute_command(f"grep -A 20 'public function reorder' {self.web_root}/src/Controllers/Admin/Pages.php")
        print(f"Pages reorder method: {pages_reorder}")

        # Check route configuration
        content_routes = self.execute_command(f"grep -B 2 -A 2 'content/reorder' {self.web_root}/config/routes.php")
        print(f"Content routes: {content_routes}")

        return True

    def cleanup_debug_files(self):
        """Clean up debug files"""
        print("\n=== CLEANING UP DEBUG FILES ===")

        self.execute_command(f"rm -f {self.web_root}/debug_session.php")
        self.execute_command(f"rm -f {self.web_root}/debug_auth.php")

        print("[INFO] Debug files cleaned up")

        return True

def main():
    """Main function"""
    if len(sys.argv) < 2:
        print("Usage: python server_debug_agent.py <command>")
        print("Commands:")
        print("  setup     - Set up testing environment")
        print("  install   - Install Playwright and dependencies")
        print("  debug     - Run debugging analysis")
        print("  test      - Run comprehensive tests")
        print("  analyze   - Analyze Content controller")
        print("  cleanup   - Clean up debug files")
        print("  full      - Run complete debugging workflow")
        return

    command = sys.argv[1]
    agent = ServerDebugAgent()

    if not agent.connect():
        return

    try:
        if command == "setup":
            agent.setup_testing_environment()
        elif command == "install":
            agent.install_playwright()
        elif command == "debug":
            agent.debug_authentication_flow()
        elif command == "test":
            agent.run_comprehensive_tests()
        elif command == "analyze":
            agent.analyze_content_controller()
        elif command == "cleanup":
            agent.cleanup_debug_files()
        elif command == "full":
            print("=== RUNNING FULL DEBUGGING WORKFLOW ===")
            agent.setup_testing_environment()
            agent.create_debug_endpoints()
            agent.install_playwright()
            agent.create_server_test_suite()
            agent.debug_authentication_flow()
            agent.analyze_content_controller()
            agent.run_comprehensive_tests()
            agent.cleanup_debug_files()
        else:
            print(f"Unknown command: {command}")

    except KeyboardInterrupt:
        print("\n[INFO] Operation interrupted by user")
    except Exception as e:
        print(f"[ERROR] {e}")

    print("\n[INFO] Debug agent completed")

if __name__ == "__main__":
    main()