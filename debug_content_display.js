const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Debugging content display on frontend...');
    console.log('==========================================');
    
    try {
        // Check homepage
        console.log('\n1. HOMEPAGE CONTENT CHECK');
        await page.goto('https://dalthaus.net/', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const title = await page.title();
        console.log(`   Page title: "${title}"`);
        
        // Check for any content
        const contentItems = await page.locator('article, .content-item, .article-item, .photobook-item').count();
        console.log(`   Content items found: ${contentItems}`);
        
        // Check for any text mentioning articles or photobooks
        const pageText = await page.textContent('body');
        const hasArticles = pageText.includes('article') || pageText.includes('Article');
        const hasPhotobooks = pageText.includes('photobook') || pageText.includes('Photobook');
        
        console.log(`   Page contains "article": ${hasArticles}`);
        console.log(`   Page contains "photobook": ${hasPhotobooks}`);
        
        // Check if showing maintenance or error
        if (pageText.includes('maintenance') || pageText.includes('Maintenance')) {
            console.log('   ⚠️  Site appears to be in maintenance mode');
        } else if (pageText.includes('error') || pageText.includes('Error')) {
            console.log('   ⚠️  Site appears to have an error');
        } else {
            console.log('   ✓ Site appears to be running normally');
        }
        
        // Check articles page specifically
        console.log('\n2. ARTICLES PAGE CONTENT CHECK');
        await page.goto('https://dalthaus.net/articles', {
            waitUntil: 'networkidle'
        });
        
        const articlesText = await page.textContent('body');
        const articlesTitle = await page.title();
        console.log(`   Articles page title: "${articlesTitle}"`);
        
        // Look for specific article titles we know exist
        if (articlesText.includes('The Case For Pure Photography')) {
            console.log('   ✓ Found "The Case For Pure Photography" article');
        } else {
            console.log('   ✗ "The Case For Pure Photography" not found');
        }
        
        if (articlesText.includes('This is a test')) {
            console.log('   ✓ Found "This is a test" article');
        } else {
            console.log('   ✗ "This is a test" not found');
        }
        
        // Check for any article listings
        const articleElements = await page.locator('h1, h2, h3, .title, .article-title').count();
        console.log(`   Article title elements found: ${articleElements}`);
        
        // Sample some text from the page
        const firstParagraph = await page.locator('p').first().textContent().catch(() => 'No paragraphs found');
        console.log(`   First paragraph: "${firstParagraph.substring(0, 100)}..."`);
        
        console.log('\n==========================================');
        console.log('Content display debug completed!');
        
    } catch (error) {
        console.error('Error during debug:', error.message);
    }
    
    await browser.close();
})();