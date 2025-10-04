/**
 * Debug script to diagnose auto-save indicator visibility issues
 * Run this in browser console on the content create/edit page
 */

function debugAutoSaveIndicator() {
    console.log('=== AUTO-SAVE INDICATOR DEBUG ===');
    
    // 1. Check if AutoSave class is available
    console.log('1. AutoSave class available:', typeof AutoSave !== 'undefined');
    
    // 2. Check if window.autoSave instance exists
    console.log('2. Window.autoSave instance:', !!window.autoSave);
    
    // 3. Check if indicator element exists
    const indicator = document.getElementById('autosave-status');
    console.log('3. Indicator element exists:', !!indicator);
    
    if (indicator) {
        // 4. Check indicator properties
        console.log('4. Indicator properties:');
        const computedStyle = window.getComputedStyle(indicator);
        const rect = indicator.getBoundingClientRect();
        
        console.log('   - Classes:', indicator.className);
        console.log('   - Position:', computedStyle.position);
        console.log('   - Top:', computedStyle.top);
        console.log('   - Right:', computedStyle.right);
        console.log('   - Z-index:', computedStyle.zIndex);
        console.log('   - Opacity:', computedStyle.opacity);
        console.log('   - Display:', computedStyle.display);
        console.log('   - Visibility:', computedStyle.visibility);
        console.log('   - Transform:', computedStyle.transform);
        console.log('   - Bounding rect:', rect);
        console.log('   - In viewport:', rect.top >= 0 && rect.left >= 0 && rect.bottom <= window.innerHeight && rect.right <= window.innerWidth);
        
        // 5. Check if any elements might be hiding it
        console.log('5. Elements at indicator position:');
        const elementsAtPosition = document.elementsFromPoint(rect.left + rect.width/2, rect.top + rect.height/2);
        elementsAtPosition.forEach((el, index) => {
            console.log(`   [${index}]`, el.tagName, el.className, 'z-index:', window.getComputedStyle(el).zIndex);
        });
    }
    
    // 6. Check styles element
    const stylesElement = document.getElementById('autosave-styles');
    console.log('6. Styles element exists:', !!stylesElement);
    
    // 7. Check form and initialization
    const form = document.getElementById('contentForm');
    console.log('7. Content form exists:', !!form);
    
    if (form) {
        console.log('   - Form action:', form.action);
        console.log('   - Is create mode:', form.action.includes('/store') || form.action.includes('/create'));
        console.log('   - Is edit mode:', form.action.includes('/update'));
    }
    
    // 8. Force show indicator for testing
    console.log('8. Force showing indicator...');
    if (window.autoSave && typeof window.autoSave.showStatus === 'function') {
        window.autoSave.showStatus('info', 'DEBUG: Testing indicator visibility');
    } else {
        // Manual creation for testing
        console.log('   Creating manual indicator for testing...');
        createTestIndicator();
    }
    
    console.log('=== DEBUG COMPLETE ===');
}

function createTestIndicator() {
    // Remove any existing test indicator
    const existing = document.getElementById('test-autosave-indicator');
    if (existing) existing.remove();
    
    const testIndicator = document.createElement('div');
    testIndicator.id = 'test-autosave-indicator';
    testIndicator.style.cssText = `
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
        z-index: 99999 !important;
        background: #ff6b6b !important;
        color: white !important;
        padding: 12px 20px !important;
        border-radius: 6px !important;
        font-family: system-ui, sans-serif !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        opacity: 1 !important;
        transform: none !important;
        pointer-events: none !important;
        border: 3px solid #fff !important;
    `;
    testIndicator.textContent = 'TEST INDICATOR - If you see this, positioning works!';
    
    document.body.appendChild(testIndicator);
    
    console.log('Test indicator created. If you can\'t see a red box in the top-right corner, there may be a browser-level issue.');
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (testIndicator && testIndicator.parentNode) {
            testIndicator.remove();
        }
    }, 10000);
}

function fixAutoSaveIndicator() {
    console.log('=== ATTEMPTING TO FIX AUTO-SAVE INDICATOR ===');
    
    // Force recreation of the indicator
    if (window.autoSave) {
        console.log('Recreating AutoSave indicator...');
        window.autoSave.createStatusIndicator();
        
        // Force show a status
        setTimeout(() => {
            window.autoSave.showStatus('info', 'Auto-save indicator fixed - please check top-right corner');
        }, 500);
    } else {
        console.log('AutoSave instance not found. Attempting to reinitialize...');
        
        const form = document.getElementById('contentForm');
        if (form) {
            try {
                window.autoSave = new AutoSave('contentForm');
                console.log('AutoSave reinitialized successfully');
            } catch (error) {
                console.error('Failed to reinitialize AutoSave:', error);
            }
        } else {
            console.error('Content form not found');
        }
    }
}

// Export functions to global scope for easy console access
window.debugAutoSaveIndicator = debugAutoSaveIndicator;
window.createTestIndicator = createTestIndicator;
window.fixAutoSaveIndicator = fixAutoSaveIndicator;

console.log('Auto-save indicator debug functions loaded. Run:');
console.log('- debugAutoSaveIndicator() - to diagnose the issue');
console.log('- createTestIndicator() - to test positioning');
console.log('- fixAutoSaveIndicator() - to attempt a fix');