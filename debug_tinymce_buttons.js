// Debug script to test TinyMCE button functionality
// Run this in browser console on admin content creation page

console.log('🧪 Starting TinyMCE Button Debug Test...');

function testTinyMCEButtons() {
    // Check if TinyMCE is loaded
    if (typeof tinymce === 'undefined') {
        console.error('❌ TinyMCE is not loaded');
        return {
            success: false,
            error: 'TinyMCE not found'
        };
    }
    
    console.log('✅ TinyMCE is loaded');
    
    // Check for editors
    const editors = tinymce.get();
    console.log(`Found ${editors.length} TinyMCE editor(s)`);
    
    if (editors.length === 0) {
        console.warn('⚠️ No TinyMCE editors found');
        return {
            success: false,
            error: 'No editors found'
        };
    }
    
    const results = {
        success: true,
        editors: editors.length,
        buttons: {}
    };
    
    // Test each editor
    editors.forEach((editor, index) => {
        console.log(`\n--- Testing Editor ${index + 1} (${editor.id}) ---`);
        
        // Check if editor is initialized
        if (!editor.initialized) {
            console.warn(`⚠️ Editor ${editor.id} is not fully initialized`);
            return;
        }
        
        // Get registered buttons
        const registeredButtons = editor.ui.registry.getAll().buttons;
        const customButtons = ['dualimage', 'modalimage'];
        
        console.log('All registered buttons:', Object.keys(registeredButtons));
        
        // Check for our custom buttons
        customButtons.forEach(buttonName => {
            const isRegistered = !!registeredButtons[buttonName];
            results.buttons[buttonName] = isRegistered;
            console.log(`${buttonName}: ${isRegistered ? '✅ Registered' : '❌ Not registered'}`);
            
            if (isRegistered) {
                const button = registeredButtons[buttonName];
                console.log(`  - Text: "${button.text || 'N/A'}"`);
                console.log(`  - Tooltip: "${button.tooltip || 'N/A'}"`);
            }
        });
        
        // Check toolbar configuration
        const toolbarConfig = editor.settings?.toolbar || 'undefined';
        console.log('Toolbar config:', toolbarConfig);
        
        // Check if buttons appear in toolbar
        customButtons.forEach(buttonName => {
            const inToolbar = toolbarConfig.includes(buttonName);
            console.log(`${buttonName} in toolbar: ${inToolbar ? '✅ Yes' : '❌ No'}`);
        });
        
        // Try to find buttons in DOM
        const toolbarElement = document.querySelector('.tox-toolbar');
        if (toolbarElement) {
            console.log('✅ Toolbar DOM element found');
            
            // Look for custom button elements
            const dualImageBtn = toolbarElement.querySelector('button[title*="Dual Image"], button:has-text("🖼️📱")');
            
            console.log(`Dual Image button in DOM: ${dualImageBtn ? '✅ Found' : '❌ Not found'}`);
            
            // Count all toolbar buttons
            const allButtons = toolbarElement.querySelectorAll('button');
            console.log(`Total toolbar buttons in DOM: ${allButtons.length}`);
            
            // List first 10 button texts for debugging
            Array.from(allButtons).slice(0, 10).forEach((btn, i) => {
                const text = btn.textContent?.trim() || '';
                const title = btn.title || '';
                console.log(`  Button ${i + 1}: "${text}" (title: "${title}")`);
            });
            
        } else {
            console.warn('⚠️ Toolbar DOM element not found');
        }
    });
    
    return results;
}

// Test dual image button functionality
function testDualImageFunction() {
    if (typeof tinymce === 'undefined' || tinymce.get().length === 0) {
        console.error('❌ No TinyMCE editor available for testing');
        return;
    }
    
    const editor = tinymce.get()[0];
    
    // Test if we can trigger the dual image button
    try {
        console.log('🖼️ Testing dual image button functionality...');
        
        // Try to get the dual image button
        const buttons = editor.ui.registry.getAll().buttons;
        if (buttons.dualimage) {
            console.log('✅ Dual image button found, attempting to trigger...');
            
            // Trigger the button action
            if (buttons.dualimage.onAction) {
                buttons.dualimage.onAction();
                console.log('✅ Dual image button action triggered successfully');
            } else {
                console.warn('⚠️ Dual image button has no onAction function');
            }
        } else {
            console.error('❌ Dual image button not found in registry');
        }
    } catch (error) {
        console.error('❌ Error testing dual image button:', error.message);
    }
}

// Run the test
const testResults = testTinyMCEButtons();
console.log('\n🎯 Test Results Summary:', testResults);

// Add function to window for manual testing
window.testTinyMCEButtons = testTinyMCEButtons;
window.testDualImageFunction = testDualImageFunction;

console.log('\n📝 Manual testing functions added to window:');
console.log('- window.testTinyMCEButtons() - Run full button test');
console.log('- window.testDualImageFunction() - Test dual image button functionality');

return testResults;