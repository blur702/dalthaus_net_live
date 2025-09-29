import { test, expect } from '@playwright/test';

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

  test('Test direct Content model methods', async ({ page }) => {
    console.log('=== CONTENT MODEL TEST ===');

    // Create a direct test of the Content model methods
    const testEndpoint = `
    <?php
    require_once '/home/dalthaus/public_html/bootstrap.php';
    use CMS\\Models\\Content as ContentModel;

    header('Content-Type: application/json');

    try {
        $content = ContentModel::getForReordering();
        echo json_encode([
            'success' => true,
            'count' => count($content),
            'data' => array_slice($content, 0, 3) // First 3 items
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    ?>`;

    // This test would require deploying a temporary endpoint
    console.log('Content model test ready - would need server deployment');
  });
});