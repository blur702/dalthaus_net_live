const { test, expect } = require('@playwright/test');

test.describe('Debug Article Data', () => {
    test('should inspect article data structure on admin page', async ({ page }) => {
        console.log('🔍 Debugging article data structure...');
        
        // Navigate to login page
        await page.goto('https://dalthaus.net/admin/login');
        await page.waitForLoadState('networkidle');
        
        // Login
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // Navigate to admin articles page
        console.log('📄 Navigating to admin articles page...');
        await page.goto('https://dalthaus.net/admin/articles');
        await page.waitForLoadState('networkidle');
        
        // Get the raw HTML to inspect the generated links
        const tableRows = await page.locator('table tbody tr').all();
        
        console.log(`📋 Found ${tableRows.length} article rows`);
        
        for (let i = 0; i < Math.min(tableRows.length, 3); i++) {
            const row = tableRows[i];
            const title = await row.locator('td:first-child .text-sm.font-medium').textContent();
            const viewLink = await row.locator('td:last-child a.text-green-600:has-text("View")').getAttribute('href');
            
            console.log(`\nArticle ${i + 1}:`);
            console.log(`  Title: ${title?.trim()}`);
            console.log(`  View Link href: ${viewLink}`);
        }
        
        // Also check the page source around the links
        const pageContent = await page.content();
        const viewLinkMatches = pageContent.match(/href="[^"]*"[^>]*>View</g);
        
        console.log('\n🔍 Raw View link patterns found in HTML:');
        if (viewLinkMatches) {
            viewLinkMatches.forEach((match, index) => {
                console.log(`  ${index + 1}: ${match}`);
            });
        }
    });
});