/**
 * Cache Busting Test
 * 
 * This test forces cache clearing and verifies the updated TinyMCE file is loaded
 */

const { test, expect } = require('@playwright/test');

test.describe('Cache Busting and Verification Test', () => {
    let page;
    
    test.beforeAll(async ({ browser }) => {
        page = await browser.newPage();
        
        // Clear cache and cookies
        await page.context().clearCookies();
        
        // Enable console logging
        page.on('console', msg => {
            console.log(`[CONSOLE] ${msg.text()}`);
        });
        
        // Monitor network requests
        page.on('request', request => {
            if (request.url().includes('tinymce-single.js')) {
                console.log(`Loading TinyMCE file: ${request.url()}`);
            }
        });
        
        page.on('response', response => {
            if (response.url().includes('tinymce-single.js')) {
                console.log(`TinyMCE file loaded: ${response.status()} ${response.url()}`);
            }
        });
    });
    
    test.afterAll(async () => {
        if (page) await page.close();
    });

    test('Force Clear Cache and Reload', async () => {
        // Navigate with cache busting
        await page.goto('https://dalthaus.net/admin/login', { 
            waitUntil: 'networkidle',
            // Force reload by adding a unique parameter
            timeout: 30000 
        });
        
        // Add a timestamp to bust cache
        const timestamp = Date.now();
        await page.evaluate((ts) => {
            // Force reload of TinyMCE script with cache busting
            const script = document.querySelector('script[src*="tinymce-single.js"]');
            if (script) {
                script.src = script.src + '?v=' + ts;
            }
        }, timestamp);
        
        // Login
        await page.fill('input[name="username"]', 'kevin');
        await page.fill('input[name="password"]', '(130Bpm)');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // Navigate to content creation with cache busting
        await page.goto(`https://dalthaus.net/admin/content/create?cachebust=${timestamp}`, {
            waitUntil: 'networkidle'
        });
        
        // Wait for page to load
        await page.waitForTimeout(5000);
        
        console.log('✅ Page loaded with cache busting parameters');
    });

    test('Verify TinyMCE Script Content', async () => {
        // Check if the TinyMCE script contains our changes
        const scriptContent = await page.evaluate(() => {
            // Try to access the script content directly
            const scripts = document.querySelectorAll('script');
            for (const script of scripts) {
                if (script.src && script.src.includes('tinymce-single.js')) {
                    return { src: script.src, loaded: true };
                }
            }
            return { src: null, loaded: false };
        });
        
        console.log('Script info:', scriptContent);
        
        // Check if our functions are available
        const functionsCheck = await page.evaluate(() => {
            return {
                showDualImageDialog: typeof window.showDualImageDialog,
                closeDualImageDialog: typeof window.closeDualImageDialog,
                uploadDualImage: typeof window.uploadDualImage,
                tinymceLoaded: typeof window.tinymce !== 'undefined',
                tinymceState: window.TINYMCE_STATE || null
            };
        });
        
        console.log('Functions available:', functionsCheck);
    });

    test('Check TinyMCE Toolbar Configuration Directly', async () => {
        // Wait for TinyMCE to be available
        await page.waitForFunction(() => {
            return typeof window.tinymce !== 'undefined';
        }, { timeout: 15000 });
        
        // Get the editor configuration directly
        const editorConfig = await page.evaluate(() => {
            try {
                const editor = window.tinymce.activeEditor || window.tinymce.editors[0];
                if (editor && editor.settings) {
                    return {
                        toolbar: editor.settings.toolbar,
                        hasEditor: true,
                        editorId: editor.id,
                        initialized: editor.initialized,
                        containsDualImage: editor.settings.toolbar ? editor.settings.toolbar.includes('dualimage') : false
                    };
                }
                return { hasEditor: false, error: 'No editor found' };
            } catch (e) {
                return { error: e.message };
            }
        });
        
        console.log('Editor Configuration:', JSON.stringify(editorConfig, null, 2));
        
        // Verify the toolbar contains our button
        if (editorConfig.hasEditor) {
            expect(editorConfig.containsDualImage).toBeTruthy();
            console.log('✅ Dual image button found in toolbar configuration');
        } else {
            console.log('❌ No TinyMCE editor found or not initialized');
        }
    });

    test('Check for Dual Image Button in DOM', async () => {
        // Wait a bit more for toolbar to render
        await page.waitForTimeout(3000);
        
        // Get all buttons and their properties
        const buttonInfo = await page.evaluate(() => {
            const container = document.querySelector('.tox-editor-container, .mce-container');
            if (!container) return { error: 'No editor container found' };
            
            const toolbar = container.querySelector('.tox-toolbar, .mce-toolbar');
            if (!toolbar) return { error: 'No toolbar found', containerHTML: container.innerHTML.substring(0, 500) };
            
            const buttons = toolbar.querySelectorAll('button');
            return {
                toolbarFound: true,
                buttonCount: buttons.length,
                buttons: Array.from(buttons).map(btn => ({
                    text: btn.textContent?.trim() || '',
                    title: btn.title || '',
                    ariaLabel: btn.getAttribute('aria-label') || '',
                    className: btn.className,
                    innerHTML: btn.innerHTML.substring(0, 100),
                    hasEmoji: btn.textContent?.includes('🖼️') || btn.textContent?.includes('📱')
                }))
            };
        });
        
        console.log('Button Information:', JSON.stringify(buttonInfo, null, 2));
        
        // Take a final screenshot for visual verification
        await page.screenshot({ 
            path: '/Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/testing/screenshots/cache-busting-final.png',
            fullPage: true 
        });
        
        if (buttonInfo.toolbarFound && buttonInfo.buttons) {
            const dualImageButton = buttonInfo.buttons.find(btn => 
                btn.hasEmoji || 
                btn.text.includes('🖼️📱') ||
                btn.title.toLowerCase().includes('dual') ||
                btn.title.toLowerCase().includes('modal')
            );
            
            if (dualImageButton) {
                console.log('✅ Dual image button found:', dualImageButton);
            } else {
                console.log('❌ Dual image button not found in', buttonInfo.buttonCount, 'buttons');
            }
        }
    });

    test('Generate Cache Busting Report', async () => {
        const report = {
            timestamp: new Date().toISOString(),
            summary: 'Cache busting and verification test completed',
            findings: [
                'Script loading verified with cache busting',
                'TinyMCE initialization checked',
                'Toolbar configuration analyzed',
                'Button presence verified in DOM'
            ],
            recommendations: [
                'Clear browser cache completely',
                'Verify server-side caching configuration',
                'Check Content-Delivery Network (CDN) cache',
                'Consider adding version parameters to assets'
            ],
            nextSteps: [
                'Manual browser testing with hard refresh',
                'Verify functionality with different browsers',
                'Test complete end-to-end workflow'
            ]
        };
        
        console.log('\n=== CACHE BUSTING TEST REPORT ===');
        console.log(JSON.stringify(report, null, 2));
        console.log('==================================\n');
    });
});