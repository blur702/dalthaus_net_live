const { test, expect } = require('@playwright/test');

test('Auto-save basic functionality check', async ({ page }) => {
    // Listen for console errors
    page.on('console', msg => {
        if (msg.type() === 'error') {
            console.log('Browser console error:', msg.text());
        }
    });

    // Navigate to login page
    await page.goto('http://localhost:9000/admin/login');
    
    // Login
    await page.fill('input[name="username"]', 'kevin');
    await page.fill('input[name="password"]', '(130Bpm)');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard
    await page.waitForURL('**/admin/dashboard');
    
    // Navigate to content
    await page.goto('http://localhost:9000/admin/content');
    
    // Check if content exists
    const contentItems = await page.locator('.content-item').count();
    console.log('Found content items:', contentItems);
    
    if (contentItems > 0) {
        // Click first edit button
        await page.locator('a[href*="/edit"]').first().click();
        
        // Wait for form
        await page.waitForSelector('#contentForm');
        
        // Check if autosave script loaded
        const autoSaveExists = await page.evaluate(() => typeof window.AutoSave !== 'undefined');
        console.log('AutoSave class exists:', autoSaveExists);
        
        // Check if autosave instance exists
        const autoSaveInstance = await page.evaluate(() => typeof window.autoSave !== 'undefined');
        console.log('AutoSave instance exists:', autoSaveInstance);
        
        // Check form action to see if it contains content ID
        const formAction = await page.locator('#contentForm').getAttribute('action');
        console.log('Form action:', formAction);
        
        // Check for status indicator
        const statusExists = await page.locator('#autosave-status').count();
        console.log('Status indicator count:', statusExists);
        
    } else {
        console.log('No content found to test');
    }
});