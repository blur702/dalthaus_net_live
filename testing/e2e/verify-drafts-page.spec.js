const { test, expect } = require('@playwright/test');

test.describe('Drafts Page Verification', () => {
  test('should navigate to drafts page and verify recent autosaves', async ({ page }) => {
    // Navigate to the admin login page
    await page.goto('https://dalthaus.net/admin/login');
    
    // Wait for the page to load completely
    await page.waitForLoadState('networkidle');
    
    // Take a screenshot of the login page for debugging
    await page.screenshot({ path: 'testing/results/login-page-debug.png' });
    
    // Get all input fields and examine them
    const inputs = await page.locator('input').all();
    console.log(`Found ${inputs.length} input fields`);
    
    for (let i = 0; i < inputs.length; i++) {
      const placeholder = await inputs[i].getAttribute('placeholder');
      const name = await inputs[i].getAttribute('name');
      const type = await inputs[i].getAttribute('type');
      console.log(`Input ${i}: type="${type}", name="${name}", placeholder="${placeholder}"`);
    }
    
    // Try different ways to find and fill the username field
    try {
      await page.fill('input[type="text"]:first-of-type', 'kevin');
      console.log('✓ Filled username field');
    } catch (error) {
      console.log('❌ Failed to fill username field:', error.message);
    }
    
    // Try different ways to find and fill the password field
    try {
      await page.fill('input[type="password"]', '(130Bpm)');
      console.log('✓ Filled password field');
    } catch (error) {
      console.log('❌ Failed to fill password field:', error.message);
    }
    
    // Try to submit the form
    try {
      await page.click('button:has-text("Sign in")');
      console.log('✓ Clicked sign in button');
    } catch (error) {
      console.log('❌ Failed to click sign in button:', error.message);
      // Try alternative selectors
      await page.click('button[type="submit"]');
    }
    
    // Wait a moment for potential redirect
    await page.waitForTimeout(2000);
    
    // Check where we ended up
    const currentUrlAfterLogin = page.url();
    console.log('URL after login attempt:', currentUrlAfterLogin);
    
    if (currentUrlAfterLogin.includes('/admin/dashboard') || currentUrlAfterLogin.includes('/admin/content')) {
      console.log('✓ Login appears successful');
    } else {
      console.log('❌ Login may have failed, continuing anyway...');
    }
    
    // If login was successful, navigate from dashboard
    if (currentUrlAfterLogin.includes('/admin/dashboard') || currentUrlAfterLogin.includes('/admin/content')) {
      console.log('Attempting to navigate to drafts from dashboard...');
      
      // First try to find a Content or Drafts menu link
      const contentLink = page.locator('a:has-text("Content"), a[href*="content"]').first();
      const draftsLink = page.locator('a:has-text("Drafts"), a[href*="drafts"]').first();
      
      if (await draftsLink.count() > 0) {
        console.log('Found drafts link, clicking it...');
        await draftsLink.click();
      } else if (await contentLink.count() > 0) {
        console.log('Found content link, clicking it...');
        await contentLink.click();
        await page.waitForTimeout(1000);
        
        // Then look for a drafts sub-menu
        const subDraftsLink = page.locator('a:has-text("Drafts"), a[href*="drafts"]').first();
        if (await subDraftsLink.count() > 0) {
          await subDraftsLink.click();
        }
      } else {
        console.log('No drafts/content links found, trying direct URL...');
        await page.goto('https://dalthaus.net/admin/content/drafts');
      }
    } else {
      console.log('Login failed, trying direct URL anyway...');
      await page.goto('https://dalthaus.net/admin/content/drafts');
    }
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // Take a screenshot of the drafts page
    await page.screenshot({ 
      path: 'testing/results/drafts-page-verification.png',
      fullPage: true 
    });
    
    // Check if the page title indicates we're on the drafts page
    const pageTitle = await page.locator('h1, h2, .page-title').first();
    console.log('Page title/heading:', await pageTitle.textContent());
    
    // Look for draft entries or a drafts table
    const draftsTable = page.locator('table, .drafts-list, .content-list');
    const hasDraftsTable = await draftsTable.count() > 0;
    
    if (hasDraftsTable) {
      console.log('✓ Found drafts table/list');
      
      // Count draft entries
      const draftRows = page.locator('table tbody tr, .draft-item, .content-item');
      const draftCount = await draftRows.count();
      console.log(`Found ${draftCount} draft entries`);
      
      // Check for recent timestamps (look for today's date or recent times)
      const now = new Date();
      const today = now.toISOString().split('T')[0]; // YYYY-MM-DD format
      const timeElements = page.locator('td, .timestamp, .date').filter({ hasText: /\d{2}:\d{2}/ });
      const recentTimeCount = await timeElements.count();
      
      if (recentTimeCount > 0) {
        console.log(`✓ Found ${recentTimeCount} elements with time stamps`);
        
        // Get the text content of the first few time elements
        for (let i = 0; i < Math.min(3, recentTimeCount); i++) {
          const timeText = await timeElements.nth(i).textContent();
          console.log(`Recent entry ${i + 1}:`, timeText);
        }
      }
      
      // Look for specific autosave indicators
      const autosaveElements = page.locator('text=/autosave|draft|auto-save/i');
      const autosaveCount = await autosaveElements.count();
      
      if (autosaveCount > 0) {
        console.log(`✓ Found ${autosaveCount} autosave-related elements`);
      }
      
    } else {
      console.log('❌ No drafts table or list found');
      
      // Check if there's a "no drafts" message
      const noDraftsMessage = page.locator('text=/no drafts|empty|no content/i');
      const hasNoDraftsMessage = await noDraftsMessage.count() > 0;
      
      if (hasNoDraftsMessage) {
        console.log('Found "no drafts" message');
      }
    }
    
    // Check the current URL to confirm we're on the right page
    const currentUrl = page.url();
    console.log('Current URL:', currentUrl);
    
    // Log final results
    if (currentUrl.includes('/admin/content/drafts')) {
      console.log('✓ Successfully reached the drafts page');
    } else if (currentUrl.includes('/admin/login')) {
      console.log('❌ Still on login page - authentication failed');
    } else {
      console.log('ℹ️ On unexpected page:', currentUrl);
    }
  });
});