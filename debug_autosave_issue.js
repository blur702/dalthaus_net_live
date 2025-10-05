// Autosave Debug Test
// Add this to browser console to debug the autosave issues

console.log('🔍 Starting Autosave Debug Session');

// Function to test create draft endpoint
async function testCreateDraft() {
    console.log('📝 Testing Create Draft Endpoint');
    
    // Get CSRF token from form
    const csrfToken = document.querySelector('[name="_token"]');
    if (!csrfToken) {
        console.error('❌ No CSRF token found');
        return;
    }
    
    const formData = new FormData();
    formData.append('title', 'Debug Test Article ' + Date.now());
    formData.append('content_type', 'article');
    formData.append('_token', csrfToken.value);
    
    try {
        const response = await fetch('/admin/content/create-draft', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        console.log('📡 Response status:', response.status);
        
        const result = await response.json();
        console.log('📋 Create draft result:', result);
        
        if (result.success) {
            console.log('✅ Draft created successfully with ID:', result.content_id);
            return result.content_id;
        } else {
            console.error('❌ Draft creation failed:', result.message);
        }
    } catch (error) {
        console.error('💥 Create draft error:', error);
    }
}

// Function to test autosave endpoint
async function testAutosave(contentId) {
    console.log('💾 Testing Autosave Endpoint with ID:', contentId);
    
    const csrfToken = document.querySelector('[name="_token"]');
    if (!csrfToken) {
        console.error('❌ No CSRF token found');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', contentId);
    formData.append('field', 'title');
    formData.append('value', 'Updated Debug Test ' + Date.now());
    formData.append('_token', csrfToken.value);
    
    try {
        const response = await fetch('/admin/content/autosave', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        console.log('📡 Autosave response status:', response.status);
        
        const result = await response.json();
        console.log('📋 Autosave result:', result);
        
        if (result.success) {
            console.log('✅ Autosave successful');
        } else {
            console.error('❌ Autosave failed:', result.message);
        }
    } catch (error) {
        console.error('💥 Autosave error:', error);
    }
}

// Function to check current autosave instance
function checkAutosaveInstance() {
    console.log('🔎 Checking Current AutoSave Instance');
    
    // Check if autosave is loaded
    if (typeof window.autoSaveInstance !== 'undefined') {
        console.log('✅ AutoSave instance found');
        console.log('📊 Content ID:', window.autoSaveInstance.contentId);
        console.log('📊 Is Create Mode:', window.autoSaveInstance.isCreateMode);
        console.log('📊 Is Draft Created:', window.autoSaveInstance.isDraftCreated);
        console.log('📊 Is Destroyed:', window.autoSaveInstance.isDestroyed);
    } else {
        console.log('❌ No AutoSave instance found');
    }
    
    // Check form details
    const form = document.querySelector('form');
    if (form) {
        console.log('📝 Form action:', form.action);
        console.log('📝 Form method:', form.method);
        
        const titleField = form.querySelector('[name="title"]');
        if (titleField) {
            console.log('📝 Title field value:', titleField.value);
        }
    }
}

// Function to run full debug sequence
async function runFullDebugSequence() {
    console.log('🚀 Running Full Debug Sequence');
    
    checkAutosaveInstance();
    
    const contentId = await testCreateDraft();
    if (contentId) {
        await testAutosave(contentId);
    }
}

// Run debug
runFullDebugSequence();