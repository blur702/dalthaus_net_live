const { chromium } = require('playwright');

async function inspectLoginForm() {
    console.log('Inspecting login form structure...\n');
    
    const browser = await chromium.launch({ 
        headless: false,
        slowMo: 1000
    });
    
    const context = await browser.newContext({
        viewport: { width: 1280, height: 720 }
    });
    
    const page = await context.newPage();
    
    // Enable request/response logging
    page.on('request', request => {
        if (request.method() === 'POST') {
            console.log('POST request:', request.url());
            console.log('POST data:', request.postData());
        }
    });
    
    page.on('response', response => {
        if (response.request().method() === 'POST') {
            console.log('POST response status:', response.status());
            console.log('Response URL:', response.url());
        }
    });
    
    try {
        // Navigate to login page
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            timeout: 30000 
        });
        
        // Get page HTML to inspect form structure
        const html = await page.content();
        console.log('=== LOGIN PAGE HTML ===');
        
        // Extract form section
        const formMatch = html.match(/<form[^>]*>[\s\S]*?<\/form>/i);
        if (formMatch) {
            console.log('Form HTML:', formMatch[0]);
        }
        
        // Get all form elements
        const forms = await page.locator('form').count();
        console.log(`\nFound ${forms} form(s)`);
        
        if (forms > 0) {
            const form = page.locator('form').first();
            
            // Get form attributes
            const action = await form.getAttribute('action');
            const method = await form.getAttribute('method');
            console.log('Form action:', action);
            console.log('Form method:', method);
            
            // Get all input fields
            const inputs = await form.locator('input').all();
            console.log(`\nFound ${inputs.length} input field(s):`);
            
            for (let i = 0; i < inputs.length; i++) {
                const input = inputs[i];
                const name = await input.getAttribute('name');
                const type = await input.getAttribute('type');
                const value = await input.getAttribute('value');
                const placeholder = await input.getAttribute('placeholder');
                console.log(`  Input ${i + 1}:`, { name, type, value, placeholder });
            }
            
            // Check for CSRF token specifically
            const csrfToken = await form.locator('input[name="_token"], input[name="csrf_token"], input[name="token"]').first();
            const hasCSRF = await csrfToken.count() > 0;
            console.log('\nCSRF token present:', hasCSRF);
            
            if (hasCSRF) {
                const tokenValue = await csrfToken.getAttribute('value');
                console.log('CSRF token value:', tokenValue);
            }
            
            // Now try login with proper field inspection
            console.log('\n=== ATTEMPTING LOGIN ===');
            
            // Find the actual input fields
            const usernameInput = await form.locator('input[type="text"], input[name="username"], input[placeholder*="username" i]').first();
            const passwordInput = await form.locator('input[type="password"], input[name="password"], input[placeholder*="password" i]').first();
            
            if (await usernameInput.count() > 0 && await passwordInput.count() > 0) {
                await usernameInput.fill('kevin');
                await passwordInput.fill('(130Bpm)');
                
                const submitBtn = await form.locator('button[type="submit"], input[type="submit"]').first();
                
                if (await submitBtn.count() > 0) {
                    console.log('Submitting form...');
                    await submitBtn.click();
                    
                    // Wait for response
                    await page.waitForTimeout(5000);
                    
                    const currentUrl = page.url();
                    console.log('Current URL after submit:', currentUrl);
                    
                    // Check for error messages
                    const errorSelectors = [
                        '.error', '.alert-danger', '.flash-error', 
                        '[class*="error"]', '[class*="danger"]',
                        'div:has-text("Invalid")', 'div:has-text("incorrect")',
                        'div:has-text("failed")', 'div:has-text("wrong")'
                    ];
                    
                    for (const selector of errorSelectors) {
                        const errorElements = page.locator(selector);
                        const count = await errorElements.count();
                        if (count > 0) {
                            const texts = await errorElements.allTextContents();
                            console.log(`Found error messages (${selector}):`, texts);
                        }
                    }
                    
                    // Check page content for clues
                    const bodyText = await page.textContent('body');
                    const hasInvalidKeywords = /invalid|incorrect|wrong|failed|error/i.test(bodyText);
                    if (hasInvalidKeywords) {
                        console.log('Page contains error-related text');
                        // Extract relevant portions
                        const lines = bodyText.split('\n');
                        const errorLines = lines.filter(line => /invalid|incorrect|wrong|failed|error/i.test(line));
                        console.log('Error-related lines:', errorLines);
                    }
                }
            }
        }
        
    } catch (error) {
        console.error('Error during inspection:', error);
    } finally {
        await page.waitForTimeout(5000); // Keep open to observe
        await browser.close();
    }
}

inspectLoginForm().catch(console.error);