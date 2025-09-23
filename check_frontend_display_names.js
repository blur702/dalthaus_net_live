const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({
        headless: true
    });
    
    const context = await browser.newContext();
    const page = await context.newPage();
    
    console.log('Checking display_name on frontend...');
    console.log('==========================================');
    
    try {
        // Check homepage
        console.log('\n1. HOMEPAGE');
        await page.goto('https://dalthaus.net/', {
            waitUntil: 'networkidle',
            timeout: 30000
        });
        
        const pageContent = await page.content();
        
        // Look for author names
        if (pageContent.includes('Kevin Althaus')) {
            console.log('   ✓ "Kevin Althaus" found on homepage');
        } else {
            console.log('   ✗ "Kevin Althaus" NOT found on homepage');
        }
        
        if (pageContent.includes('dalthaus')) {
            console.log('   ✓ "dalthaus" found on homepage');
        } else {
            console.log('   ✗ "dalthaus" NOT found on homepage');
        }
        
        // Check articles page
        console.log('\n2. ARTICLES PAGE');
        await page.goto('https://dalthaus.net/articles', {
            waitUntil: 'networkidle'
        });
        
        const articlesContent = await page.content();
        
        if (articlesContent.includes('Kevin Althaus')) {
            console.log('   ✓ "Kevin Althaus" found on articles page');
        } else {
            console.log('   ✗ "Kevin Althaus" NOT found on articles page');
        }
        
        if (articlesContent.includes('dalthaus')) {
            console.log('   ✓ "dalthaus" found on articles page');
        } else {
            console.log('   ✗ "dalthaus" NOT found on articles page');
        }
        
        // Check photobooks page
        console.log('\n3. PHOTOBOOKS PAGE');
        await page.goto('https://dalthaus.net/photobooks', {
            waitUntil: 'networkidle'
        });
        
        const photobooksContent = await page.content();
        
        if (photobooksContent.includes('Kevin Althaus')) {
            console.log('   ✓ "Kevin Althaus" found on photobooks page');
        } else {
            console.log('   ✗ "Kevin Althaus" NOT found on photobooks page');
        }
        
        if (photobooksContent.includes('dalthaus')) {
            console.log('   ✓ "dalthaus" found on photobooks page');
        } else {
            console.log('   ✗ "dalthaus" NOT found on photobooks page');
        }
        
        // Check for old username "kevin" to see if it's still being used
        console.log('\n4. CHECKING FOR OLD USERNAME');
        if (pageContent.includes('"kevin"') || pageContent.includes('>kevin<')) {
            console.log('   ⚠️  Old username "kevin" still found - display_name might not be working');
        } else {
            console.log('   ✓ Old username "kevin" not found - display_name appears to be working');
        }
        
        console.log('\n==========================================');
        console.log('Frontend display_name check completed!');
        
    } catch (error) {
        console.error('Error during test:', error.message);
    }
    
    await browser.close();
})();