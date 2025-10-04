import { test, expect } from '@playwright/test';

test.describe('TinyMCE Toolbar Screenshot Capture', () => {
  test('capture high-quality toolbar screenshot for documentation', async ({ page }) => {
    console.log('📸 Capturing TinyMCE toolbar screenshot for documentation...');

    // Navigate and authenticate
    await page.goto('/admin/login', { waitUntil: 'networkidle' });
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"]')
    ]);

    // Go to content creation
    await page.goto('/admin/content/create?type=article', { waitUntil: 'networkidle' });
    
    // Wait for TinyMCE to load
    await page.waitForSelector('.tox-editor-container', { timeout: 30000 });
    await page.waitForTimeout(3000); // Extra time for complete initialization

    // Take full page screenshot
    await page.screenshot({ 
      path: 'testing/screenshots/production-tinymce-complete-verification.png',
      fullPage: true 
    });

    // Focus on just the toolbar area for a detailed view
    const toolbar = page.locator('.tox-toolbar-overlord, .tox-toolbar');
    if (await toolbar.isVisible()) {
      await toolbar.screenshot({ 
        path: 'testing/screenshots/production-tinymce-toolbar-closeup.png'
      });
    }

    // Also capture the entire editor container
    const editorContainer = page.locator('.tox-editor-container');
    if (await editorContainer.isVisible()) {
      await editorContainer.screenshot({ 
        path: 'testing/screenshots/production-tinymce-editor-container.png'
      });
    }

    console.log('✅ Screenshots captured successfully');
  });
});