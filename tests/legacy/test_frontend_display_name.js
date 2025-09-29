const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Testing display_name on frontend pages...');
    console.log('==========================================');
    
    try {
        // Test homepage
        console.log('\n1. Checking HOMEPAGE for author display names');
        await page.goto('https://dalthaus.net/', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        // Look for author names in article/photobook listings
        const authorElements = await page.locator('text=/kevin|author|Unknown/i').all();
        console.log(`   Found ${authorElements.length} author name elements`);
        
        for (let i = 0; i < Math.min(3, authorElements.length); i++) {
            const text = await authorElements[i].textContent();
            console.log(`   - Author text: "${text.trim()}"`);
        }
        
        await page.screenshot({ 
            path: 'homepage_authors.png',
            fullPage: true 
        });
        console.log('   Screenshot saved: homepage_authors.png');
        
        // Test articles page
        console.log('\n2. Checking ARTICLES page for author display names');
        await page.goto('https://dalthaus.net/articles', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const articleAuthors = await page.locator('.text-sm.text-gray-900, .text-sm').all();
        console.log(`   Found ${articleAuthors.length} potential author elements`);
        
        for (let i = 0; i < Math.min(3, articleAuthors.length); i++) {
            const text = await articleAuthors[i].textContent();
            if (text.includes('/') || text.includes('kevin')) {
                console.log(`   - Article author line: "${text.trim()}"`);
            }
        }
        
        await page.screenshot({ 
            path: 'articles_authors.png',
            fullPage: true 
        });
        console.log('   Screenshot saved: articles_authors.png');
        
        // Test photobooks page
        console.log('\n3. Checking PHOTOBOOKS page for author display names');
        await page.goto('https://dalthaus.net/photobooks', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const photobookAuthors = await page.locator('.text-sm.text-gray-900, .text-sm').all();
        console.log(`   Found ${photobookAuthors.length} potential author elements`);
        
        for (let i = 0; i < Math.min(3, photobookAuthors.length); i++) {
            const text = await photobookAuthors[i].textContent();
            if (text.includes('/') || text.includes('kevin')) {
                console.log(`   - Photobook author line: "${text.trim()}"`);
            }
        }
        
        await page.screenshot({ 
            path: 'photobooks_authors.png',
            fullPage: true 
        });
        console.log('   Screenshot saved: photobooks_authors.png');
        
        // Check a specific article if available
        console.log('\n4. Checking individual ARTICLE page');
        
        // First, find an article link
        await page.goto('https://dalthaus.net/articles', {
            waitUntil: 'networkidle'
        });
        
        const articleLink = await page.locator('a[href*="/articles/"]:has-text("Read More")').first();
        if (await articleLink.count() > 0) {
            await articleLink.click();
            await page.waitForLoadState('networkidle');
            
            const articleUrl = page.url();
            console.log(`   Opened article: ${articleUrl}`);
            
            // Look for author info on article page
            const authorInfo = await page.locator('.text-sm').all();
            for (let elem of authorInfo) {
                const text = await elem.textContent();
                if (text.includes('/')) {
                    console.log(`   Article author info: "${text.trim()}"`);
                    
                    // Check what's being displayed - username or display_name
                    if (text.includes('kevin')) {
                        console.log('   ⚠️  Still showing username "kevin" instead of display_name');
                    }
                    break;
                }
            }
            
            await page.screenshot({ 
                path: 'single_article_author.png',
                fullPage: false 
            });
            console.log('   Screenshot saved: single_article_author.png');
        } else {
            console.log('   No articles found to check');
        }
        
        console.log('\n==========================================');
        console.log('ANALYSIS:');
        console.log('If you see "kevin" everywhere, the display_name is NOT being used.');
        console.log('If you see a different name (like "Kevin Dalthaus"), display_name IS working.');
        console.log('Check the screenshots for visual confirmation.');
        
    } catch (error) {
        console.error('\nError during test:', error.message);
    }
    
    await browser.close();
})();