// PASTE THIS IN YOUR BROWSER CONSOLE TO TEST AUTO-SAVE
// This will help diagnose why you're not seeing the auto-save indicator

console.log('🔍 AUTO-SAVE DIAGNOSTIC TEST STARTING...\n');

// 1. Check if AutoSave class exists
console.log('1. AutoSave class exists?', typeof AutoSave !== 'undefined' ? '✅ YES' : '❌ NO');

// 2. Check if autoSave instance exists
console.log('2. autoSave instance exists?', typeof window.autoSave !== 'undefined' ? '✅ YES' : '❌ NO');

// 3. Check if status indicator element exists
const statusElement = document.getElementById('autosave-status');
console.log('3. Status indicator element exists?', statusElement ? '✅ YES' : '❌ NO');

// 4. If status element exists, check its visibility
if (statusElement) {
    const computed = window.getComputedStyle(statusElement);
    console.log('   - Display:', computed.display);
    console.log('   - Visibility:', computed.visibility);
    console.log('   - Opacity:', computed.opacity);
    console.log('   - Z-index:', computed.zIndex);
    console.log('   - Position:', computed.position);
    console.log('   - Top:', computed.top);
    console.log('   - Right:', computed.right);
}

// 5. Check if form exists
const form = document.getElementById('contentForm');
console.log('4. Content form exists?', form ? '✅ YES' : '❌ NO');

// 6. Try to manually create and show the indicator
console.log('\n📍 ATTEMPTING TO MANUALLY CREATE INDICATOR...\n');

function createTestIndicator() {
    // Remove any existing test indicator
    const existing = document.getElementById('test-autosave-status');
    if (existing) existing.remove();
    
    // Create new indicator
    const indicator = document.createElement('div');
    indicator.id = 'test-autosave-status';
    indicator.innerHTML = `
        <div style="
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 999999 !important;
            background: #3b82f6 !important;
            color: white !important;
            padding: 12px 20px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        ">
            🔵 TEST: Auto-save indicator should appear here!
        </div>
    `;
    document.body.appendChild(indicator);
    
    console.log('✅ Test indicator created - You should see a BLUE box in the top-right corner!');
    console.log('If you SEE the blue box, the display works and the issue is with auto-save initialization.');
    console.log('If you DON\'T see the blue box, there\'s a CSS/display issue.\n');
    
    // Remove after 5 seconds
    setTimeout(() => {
        indicator.remove();
        console.log('Test indicator removed after 5 seconds');
    }, 5000);
}

createTestIndicator();

// 7. Check if AutoSave is being initialized
console.log('\n🔧 CHECKING AUTO-SAVE INITIALIZATION...\n');

if (typeof AutoSave !== 'undefined' && form) {
    console.log('AutoSave class and form both exist. Attempting manual initialization...');
    
    // Try to manually initialize AutoSave
    try {
        // Destroy existing instance if it exists
        if (window.autoSave && window.autoSave.destroy) {
            window.autoSave.destroy();
            console.log('Destroyed existing autoSave instance');
        }
        
        // Create new instance
        window.testAutoSave = new AutoSave('contentForm');
        console.log('✅ Successfully created new AutoSave instance!');
        
        // Check the state
        setTimeout(() => {
            if (window.testAutoSave) {
                console.log('\nAutoSave State:');
                console.log('- Is enabled:', window.testAutoSave.isEnabled);
                console.log('- Is create mode:', window.testAutoSave.isCreateMode);
                console.log('- Content ID:', window.testAutoSave.contentId);
                console.log('- Is destroyed:', window.testAutoSave.isDestroyed);
            }
        }, 1000);
        
    } catch (error) {
        console.error('❌ Failed to initialize AutoSave:', error);
    }
} else {
    if (typeof AutoSave === 'undefined') {
        console.log('❌ AutoSave class not found - Script may not be loaded properly');
    }
    if (!form) {
        console.log('❌ Content form not found - Cannot initialize auto-save');
    }
}

// 8. Force show a status message
console.log('\n💡 FORCING STATUS MESSAGE...\n');

function forceShowStatus() {
    // Create or get status element
    let status = document.getElementById('autosave-status');
    if (!status) {
        status = document.createElement('div');
        status.id = 'autosave-status';
        document.body.appendChild(status);
    }
    
    // Set content and force visibility
    status.innerHTML = `
        <div style="
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 999999 !important;
            background: #10b981 !important;
            color: white !important;
            padding: 12px 20px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        ">
            ✅ Auto-save is working! (Manual test)
        </div>
    `;
    
    console.log('✅ Forced status message to show - Look at top-right corner!');
    
    // Remove after 5 seconds
    setTimeout(() => {
        status.remove();
        console.log('Status message removed after 5 seconds');
    }, 5000);
}

// Wait a bit then force show status
setTimeout(forceShowStatus, 2000);

console.log('\n📊 DIAGNOSTIC COMPLETE\n');
console.log('You should see:');
console.log('1. A BLUE test box (appears immediately)');
console.log('2. A GREEN status box (appears after 2 seconds)');
console.log('\nIf you see these boxes, the display works fine.');
console.log('If not, there may be CSS conflicts or browser issues.');