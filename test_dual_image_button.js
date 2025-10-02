/**
 * Test script to check if the dual image button appears in TinyMCE toolbar
 * Run this in the browser console on a content edit page
 */

// Wait for TinyMCE to be fully loaded
function testDualImageButton() {
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE is not loaded');
        return;
    }
    
    const editor = tinymce.get('body');
    if (!editor) {
        console.error('TinyMCE editor with ID "body" not found');
        return;
    }
    
    console.log('Editor found:', editor.id);
    
    // Check if the dual image button is registered
    const toolbar = editor.ui.registry.getAll().buttons;
    if (toolbar.dualimage) {
        console.log('✅ Dual image button is registered:', toolbar.dualimage);
    } else {
        console.error('❌ Dual image button is NOT registered');
        console.log('Available buttons:', Object.keys(toolbar));
    }
    
    // Check if the button appears in the toolbar
    const toolbarElement = editor.getContainer().querySelector('.tox-toolbar__group');
    if (toolbarElement) {
        const buttons = toolbarElement.querySelectorAll('button');
        const dualImageButton = Array.from(buttons).find(btn => 
            btn.title && btn.title.includes('Insert image with modal view')
        );
        
        if (dualImageButton) {
            console.log('✅ Dual image button found in toolbar:', dualImageButton);
        } else {
            console.error('❌ Dual image button NOT found in toolbar');
            console.log('Available buttons in toolbar:', Array.from(buttons).map(btn => btn.title || btn.textContent));
        }
    }
    
    // Test the showDualImageDialog function
    if (typeof showDualImageDialog === 'function') {
        console.log('✅ showDualImageDialog function is available');
    } else {
        console.error('❌ showDualImageDialog function is NOT available');
    }
}

// Run the test
console.log('Testing dual image button...');
setTimeout(testDualImageButton, 2000); // Wait 2 seconds for TinyMCE to fully load