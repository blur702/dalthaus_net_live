const { test, expect } = require('@playwright/test');

test.describe('Autosave Debugging', () => {
    test.beforeEach(async ({ page }) => {
        // Login first
        await page.goto('https://dalthaus.net/admin/login');
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin/dashboard');
    });

    test('Debug autosave flow step by step', async ({ page }) => {
        console.log('🔍 Starting autosave debugging...');
        
        // Enable console logging
        page.on('console', msg => {
            if (msg.type() === 'error') {
                console.log('❌ CONSOLE ERROR:', msg.text());
            } else if (msg.text().includes('AutoSave')) {
                console.log('🤖 AUTOSAVE LOG:', msg.text());
            }
        });

        // Enable network monitoring
        page.on('requestfailed', request => {
            console.log('🌐 FAILED REQUEST:', request.url(), request.failure().errorText);
        });

        page.on('response', async response => {
            if (response.url().includes('autosave') || response.url().includes('create-draft')) {
                console.log('📡 RESPONSE:', response.url(), response.status());
                if (response.status() !== 200) {
                    console.log('❌ Response text:', await response.text());
                }
            }
        });

        // Navigate to create article page
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        await page.waitForLoadState('networkidle');
        
        console.log('📝 Page loaded, checking autosave initialization...');
        
        // Check if autosave is initialized
        const autosaveInitialized = await page.evaluate(() => {
            return {
                hasInstance: typeof window.autoSaveInstance !== 'undefined',
                hasAutoSave: typeof window.autoSave !== 'undefined',
                instanceDetails: window.autoSaveInstance ? {
                    isDestroyed: window.autoSaveInstance.isDestroyed,
                    formId: window.autoSaveInstance.form?.id
                } : null,
                autoSaveDetails: window.autoSave ? {
                    isDestroyed: window.autoSave.isDestroyed,
                    formId: window.autoSave.form?.id
                } : null
            };
        });
        console.log('🎯 Autosave state:', autosaveInitialized);
        
        if (autosaveInitialized.hasAutoSave) {
            const autosaveState = await page.evaluate(() => {
                return {
                    contentId: window.autoSave.contentId,
                    isCreateMode: window.autoSave.isCreateMode,
                    isDraftCreated: window.autoSave.isDraftCreated
                };
            });
            console.log('📊 Autosave state:', autosaveState);
        }

        // Test step 1: Enter title to trigger draft creation
        console.log('🔤 Step 1: Entering title...');
        await page.fill('input[name="title"]', 'Debug Test Article ' + Date.now());
        
        // Wait a moment for autosave to trigger
        await page.waitForTimeout(3000);
        
        // Check autosave status after title entry
        const statusAfterTitle = await page.evaluate(() => {
            if (typeof window.autoSave === 'undefined') return 'No autosave instance';
            return {
                contentId: window.autoSave.contentId,
                isCreateMode: window.autoSave.isCreateMode,
                isDraftCreated: window.autoSave.isDraftCreated,
                statusElement: document.querySelector('#autosave-status')?.textContent || 'No status element'
            };
        });
        console.log('📊 Status after title:', statusAfterTitle);

        // Test step 2: Manually test create-draft endpoint
        console.log('🧪 Step 2: Testing create-draft endpoint manually...');
        const createDraftResult = await page.evaluate(async () => {
            const csrfToken = document.querySelector('[name="_token"]');
            if (!csrfToken) return { error: 'No CSRF token' };
            
            const formData = new FormData();
            formData.append('title', 'Manual Test ' + Date.now());
            formData.append('content_type', 'article');
            formData.append('_token', csrfToken.value);
            
            try {
                const response = await fetch('/admin/content/create-draft', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                return { 
                    status: response.status, 
                    result: result,
                    success: result.success,
                    contentId: result.content_id 
                };
            } catch (error) {
                return { error: error.message };
            }
        });
        console.log('🧪 Create draft result:', createDraftResult);

        // Test step 3: If draft creation worked, test autosave
        if (createDraftResult.success && createDraftResult.contentId) {
            console.log('💾 Step 3: Testing autosave with content ID:', createDraftResult.contentId);
            
            const autosaveResult = await page.evaluate(async (contentId) => {
                const csrfToken = document.querySelector('[name="_token"]');
                if (!csrfToken) return { error: 'No CSRF token' };
                
                const formData = new FormData();
                formData.append('id', contentId);
                formData.append('field', 'title');
                formData.append('value', 'Updated Title ' + Date.now());
                formData.append('_token', csrfToken.value);
                
                try {
                    const response = await fetch('/admin/content/autosave', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    const result = await response.json();
                    return { 
                        status: response.status, 
                        result: result,
                        success: result.success 
                    };
                } catch (error) {
                    return { error: error.message };
                }
            }, createDraftResult.contentId);
            console.log('💾 Autosave result:', autosaveResult);
        }

        // Test step 4: Check current page state
        console.log('🔍 Step 4: Final state check...');
        const finalState = await page.evaluate(() => {
            return {
                currentUrl: window.location.href,
                formAction: document.querySelector('form')?.action || 'No form',
                contentFormAction: document.querySelector('#contentForm')?.action || 'No content form',
                titleValue: document.querySelector('[name="title"]')?.value || 'No title field',
                statusText: document.querySelector('#autosave-status')?.textContent || 'No status',
                hasAutosaveInstance: typeof window.autoSave !== 'undefined'
            };
        });
        console.log('🔍 Final state:', finalState);

        // Take screenshot for visual inspection
        await page.screenshot({ 
            path: 'testing/results/autosave-debug-screenshot.png',
            fullPage: true 
        });
        console.log('📸 Screenshot saved for visual inspection');
    });

    test('Check TinyMCE button issues', async ({ page }) => {
        console.log('🔧 Checking TinyMCE button registration issues...');
        
        // Enable console logging for TinyMCE
        page.on('console', msg => {
            if (msg.text().includes('TinyMCE') || msg.text().includes('dualimage')) {
                console.log('🔧 TINYMCE LOG:', msg.type(), msg.text());
            }
        });

        // Navigate to create article page
        await page.goto('https://dalthaus.net/admin/content/create?type=article');
        await page.waitForLoadState('networkidle');
        
        // Wait for TinyMCE to initialize
        await page.waitForTimeout(5000);
        
        // Check TinyMCE state
        const tinyMCEState = await page.evaluate(() => {
            return {
                tinyMCELoaded: typeof tinymce !== 'undefined',
                editorCount: typeof tinymce !== 'undefined' ? tinymce.editors.length : 0,
                hasBodyEditor: !!tinymce?.get('body'),
                toolbarElements: document.querySelectorAll('.tox-toolbar').length,
                imageButtons: document.querySelectorAll('[aria-label*="image"], [title*="image"]').length
            };
        });
        console.log('🔧 TinyMCE state:', tinyMCEState);

        // Take screenshot of TinyMCE area
        await page.screenshot({ 
            path: 'testing/results/tinymce-debug-screenshot.png',
            fullPage: true 
        });
    });
});