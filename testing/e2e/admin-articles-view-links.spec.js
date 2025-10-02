const { test, expect } = require('@playwright/test');

test.describe('Admin Articles View Links Test', () => {
    test('should check View links on admin articles page', async ({ page }) => {
        console.log('🔍 Testing View links on admin articles page...');
        
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
        
        // Take screenshot of the articles page
        await page.screenshot({ path: 'tests/screenshots/admin-articles-page.png', fullPage: true });
        
        // Check if articles exist
        const articlesTable = await page.locator('table');
        const hasArticles = await articlesTable.isVisible();
        
        if (!hasArticles) {
            console.log('❌ No articles table found on the page');
            return;
        }
        
        // Find View links specifically in the table actions column 
        const viewLinks = await page.locator('table tbody tr td:last-child a.text-green-600:has-text("View")').all();
        console.log(`📋 Found ${viewLinks.length} View links in table`);
        
        const linkResults = [];
        
        for (let i = 0; i < Math.min(viewLinks.length, 5); i++) { // Test up to 5 links
            const link = viewLinks[i];
            const href = await link.getAttribute('href');
            
            console.log(`🔗 Link ${i + 1}: ${href}`);
            
            linkResults.push({
                index: i + 1,
                href: href,
                isExternal: href?.startsWith('http')
            });
            
            // Test the link directly without clicking
            try {
                const response = await page.request.get(`https://dalthaus.net${href}`);
                const status = response.status();
                const url = response.url();
                
                console.log(`  ↳ Response status: ${status}`);
                console.log(`  ↳ Final URL: ${url}`);
                console.log(`  ↳ Is 404: ${status === 404}`);
                
                linkResults[i].actualUrl = url;
                linkResults[i].status = status;
                linkResults[i].is404 = status === 404;
                
            } catch (error) {
                console.log(`  ↳ Error loading page: ${error.message}`);
                linkResults[i].error = error.message;
            }
        }
        
        // Print summary
        console.log('\n📊 VIEW LINKS ANALYSIS SUMMARY:');
        console.log('================================');
        
        for (const result of linkResults) {
            console.log(`\nLink ${result.index}:`);
            console.log(`  Generated URL: ${result.href}`);
            console.log(`  Actual URL: ${result.actualUrl || 'Failed to load'}`);
            console.log(`  Status Code: ${result.status || 'N/A'}`);
            console.log(`  Status: ${result.is404 ? '❌ 404 ERROR' : result.error ? '❌ LOAD ERROR' : '✅ OK'}`);
            if (result.error) {
                console.log(`  Error: ${result.error}`);
            }
        }
        
        // Check for URL pattern issues
        const urlPatterns = linkResults.map(r => r.href).filter(Boolean);
        const uniquePatterns = [...new Set(urlPatterns.map(url => {
            if (url?.startsWith('/article/')) {
                return '/article/{alias}';
            }
            return url;
        }))];
        
        console.log(`\n🔍 URL PATTERNS DETECTED:`);
        uniquePatterns.forEach(pattern => {
            console.log(`  ${pattern}`);
        });
        
        // Report any 404s
        const errorCount = linkResults.filter(r => r.is404 || r.error).length;
        if (errorCount > 0) {
            console.log(`\n❌ ${errorCount} out of ${linkResults.length} View links are broken!`);
        } else {
            console.log(`\n✅ All ${linkResults.length} View links are working correctly!`);
        }
    });
});