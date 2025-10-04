/**
 * Auto-save Debug Script
 * Run this in the browser console on content create/edit pages
 * to diagnose auto-save indicator visibility issues
 */

console.log('🔍 Auto-save Debug Script Started');
console.log('========================================');

// Check if we're on the right page
const isContentPage = window.location.pathname.includes('/admin/content');
console.log('📍 On content page:', isContentPage);

if (!isContentPage) {
    console.log('❌ Not on a content page - auto-save debug not relevant');
} else {
    console.log('✅ On content page - proceeding with debug');
}

// Check if form exists
const form = document.getElementById('contentForm');
console.log('📋 Content form found:', !!form);

if (form) {
    console.log('📋 Form action:', form.action);
    console.log('📋 Form method:', form.method);
}

// Check if autosave.js loaded
const autosaveScripts = document.querySelectorAll('script[src*="autosave.js"]');
console.log('📜 Autosave script tags found:', autosaveScripts.length);

autosaveScripts.forEach((script, index) => {
    console.log(`📜 Script ${index + 1}:`, script.src);
    console.log(`📜 Script ${index + 1} loaded:`, script.readyState !== 'loading');
});

// Check if AutoSave class is available
console.log('🏗️  AutoSave class available:', typeof AutoSave !== 'undefined');

// Check if auto-save instance exists
console.log('🤖 window.autoSave instance:', !!window.autoSave);

if (window.autoSave) {
    console.log('🤖 AutoSave instance details:');
    console.log('   - contentId:', window.autoSave.contentId);
    console.log('   - isEnabled:', window.autoSave.isEnabled);
    console.log('   - isCreateMode:', window.autoSave.isCreateMode);
    console.log('   - isDraftCreated:', window.autoSave.isDraftCreated);
    console.log('   - isDestroyed:', window.autoSave.isDestroyed);
}

// Check for status indicator element
const statusIndicator = document.getElementById('autosave-status');
console.log('🎯 Status indicator element exists:', !!statusIndicator);

if (statusIndicator) {
    console.log('🎯 Status indicator details:');
    console.log('   - Visible:', statusIndicator.offsetParent !== null);
    console.log('   - Classes:', statusIndicator.className);
    console.log('   - Content:', statusIndicator.innerHTML.substring(0, 100) + '...');
    console.log('   - Computed styles:', window.getComputedStyle(statusIndicator));
    console.log('   - Opacity:', window.getComputedStyle(statusIndicator).opacity);
    console.log('   - Transform:', window.getComputedStyle(statusIndicator).transform);
    console.log('   - Position:', {
        top: window.getComputedStyle(statusIndicator).top,
        right: window.getComputedStyle(statusIndicator).right,
        zIndex: window.getComputedStyle(statusIndicator).zIndex
    });
}

// Check for CSS styles
const autosaveStyles = document.getElementById('autosave-styles');
console.log('🎨 Autosave styles element exists:', !!autosaveStyles);

if (autosaveStyles) {
    console.log('🎨 Style rules count:', autosaveStyles.textContent.split('{').length - 1);
}

// Check for watched fields
const watchedFields = ['title', 'teaser', 'body'];
console.log('🔍 Watched fields check:');

watchedFields.forEach(fieldName => {
    const field = document.querySelector(`[name="${fieldName}"]`);
    console.log(`   - ${fieldName}:`, !!field, field ? `(value length: ${field.value.length})` : '');
});

// Check browser console for errors
console.log('🚨 Check browser console for any JavaScript errors');

// Check network tab
console.log('🌐 Check Network tab for:');
console.log('   - /assets/js/autosave.js loads successfully');
console.log('   - /admin/content/autosave endpoint availability');
console.log('   - /admin/content/create-draft endpoint availability');

// Test endpoints manually
console.log('🧪 Testing endpoint availability...');

// Test autosave endpoint
if (form && window.autoSave && window.autoSave.contentId) {
    const formData = new FormData();
    formData.append('id', window.autoSave.contentId);
    formData.append('field', 'title');
    formData.append('value', 'test');
    
    const csrfToken = form.querySelector('[name="_token"]');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    
    fetch('/admin/content/autosave', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('🧪 Autosave endpoint test - Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('🧪 Autosave endpoint test - Response:', data);
    })
    .catch(error => {
        console.log('🧪 Autosave endpoint test - Error:', error);
    });
}

// Test create-draft endpoint for create mode
if (form && form.action.includes('/content/store')) {
    console.log('🧪 Testing create-draft endpoint...');
    
    const formData = new FormData();
    formData.append('title', 'Test Draft Title');
    formData.append('content_type', 'article');
    
    const csrfToken = form.querySelector('[name="_token"]');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    
    fetch('/admin/content/create-draft', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('🧪 Create-draft endpoint test - Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('🧪 Create-draft endpoint test - Response:', data);
    })
    .catch(error => {
        console.log('🧪 Create-draft endpoint test - Error:', error);
    });
}

// Manual initialization test
console.log('🔧 Manual initialization test available:');
console.log('Run: manualAutoSaveTest()');

window.manualAutoSaveTest = function() {
    console.log('🔧 Starting manual AutoSave initialization...');
    
    if (window.autoSave) {
        console.log('🔧 Destroying existing instance...');
        window.autoSave.destroy();
    }
    
    try {
        window.autoSave = new AutoSave('contentForm');
        console.log('🔧 New AutoSave instance created:', !!window.autoSave);
        
        // Force show status
        setTimeout(() => {
            if (window.autoSave && typeof window.autoSave.showStatus === 'function') {
                console.log('🔧 Testing status display...');
                window.autoSave.showStatus('info', 'Manual test - Auto-save initialized');
            }
        }, 1000);
        
    } catch (error) {
        console.log('🔧 Manual initialization error:', error);
    }
};

// Force status indicator test
console.log('🎯 Force status indicator test available:');
console.log('Run: forceStatusTest()');

window.forceStatusTest = function() {
    console.log('🎯 Creating force status indicator...');
    
    // Remove existing if any
    const existing = document.getElementById('autosave-status');
    if (existing) {
        existing.remove();
    }
    
    // Create new one
    const statusDiv = document.createElement('div');
    statusDiv.id = 'autosave-status';
    statusDiv.className = 'autosave-status show info';
    statusDiv.innerHTML = `
        <div class="autosave-status-content">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>Force Test - Status Indicator Working!</span>
        </div>
    `;
    
    // Add styles
    statusDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: #3b82f6;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        font-size: 14px;
        opacity: 1;
        transform: translateY(0);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(statusDiv);
    console.log('🎯 Force status indicator created and should be visible in top-right corner');
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        statusDiv.remove();
        console.log('🎯 Force status indicator removed');
    }, 5000);
};

console.log('========================================');
console.log('🔍 Auto-save Debug Script Complete');
console.log('📋 Check the output above for issues');
console.log('🧪 Run endpoint tests if needed');
console.log('🔧 Run manualAutoSaveTest() to test initialization');
console.log('🎯 Run forceStatusTest() to test visibility');