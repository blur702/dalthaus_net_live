/**
 * Auto-save functionality for content forms
 * Automatically saves form data periodically and on user interaction
 */

class AutoSave {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        if (!this.form) {
            console.warn('AutoSave: Form not found with ID:', formId);
            return;
        }

        // Configuration
        this.options = {
            saveInterval: 30000, // 30 seconds
            debounceDelay: 2000, // 2 seconds after typing stops
            endpoint: '/admin/content/autosave',
            createEndpoint: '/admin/content/create-draft',
            watchedFields: ['title', 'teaser', 'body'],
            ...options
        };

        // State
        this.contentId = null;
        this.isEnabled = false;
        this.isCreateMode = false;
        this.isDraftCreated = false;
        this.lastSaved = {};
        this.saveTimeout = null;
        this.intervalId = null;
        this.isDestroyed = false;

        // Get content ID from form action or hidden field
        this.extractContentId();
        
        // Initialize for both edit and create forms
        this.init();
    }

    extractContentId() {
        // Try to get ID from form action URL pattern: /admin/content/{id}/update
        const actionMatch = this.form.action.match(/\/admin\/content\/(\d+)\/update/);
        if (actionMatch) {
            this.contentId = parseInt(actionMatch[1]);
            return;
        }

        // Check if this is a create form
        if (this.form.action.includes('/content/store') || this.form.action.includes('/content/create')) {
            this.isCreateMode = true;
            return;
        }

        // Fallback: look for hidden ID field
        const idField = this.form.querySelector('input[name="id"]');
        if (idField && idField.value) {
            this.contentId = parseInt(idField.value);
        }
    }

    init() {
        console.log('AutoSave: Starting initialization...');
        
        this.createStatusIndicator();
        this.attachEventListeners();

        if (this.contentId) {
            // Edit mode - enable auto-save immediately
            this.isEnabled = true;
            this.startPeriodicSave();
            this.showStatus('success', 'Auto-save enabled for content ID: ' + this.contentId);
            console.log('AutoSave: Initialized for content ID:', this.contentId);
        } else if (this.isCreateMode) {
            // Create mode - wait for title to be entered
            this.isEnabled = false;
            this.showStatus('info', 'Auto-save will start after entering title');
            console.log('AutoSave: Initialized for create mode, waiting for title');
        } else {
            console.warn('AutoSave: Unable to determine mode, auto-save disabled');
            return;
        }
        
        console.log('AutoSave: Initialization complete');
    }

    createStatusIndicator() {
        console.log('AutoSave: Creating status indicator...');
        
        // Remove existing indicator if it exists
        const existing = document.getElementById('autosave-status');
        if (existing) {
            console.log('AutoSave: Removing existing status indicator');
            existing.remove();
        }

        const statusDiv = document.createElement('div');
        statusDiv.id = 'autosave-status';
        statusDiv.className = 'autosave-status';
        statusDiv.innerHTML = `
            <div class="autosave-status-content">
                <svg class="autosave-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="autosave-text">Auto-save ready</span>
            </div>
        `;

        // Always append to body for consistent positioning
        document.body.appendChild(statusDiv);
        console.log('AutoSave: Status indicator created and appended to body');

        // Add CSS styles
        this.addStatusStyles();
        
        // Verify the element is in the DOM
        const verification = document.getElementById('autosave-status');
        console.log('AutoSave: Status indicator verification:', !!verification);
        if (verification) {
            console.log('AutoSave: Status indicator classes:', verification.className);
        }
    }

    addStatusStyles() {
        console.log('AutoSave: Adding status styles...');
        
        // Remove existing styles if they exist
        const existing = document.getElementById('autosave-styles');
        if (existing) {
            console.log('AutoSave: Removing existing styles');
            existing.remove();
        }

        const style = document.createElement('style');
        style.id = 'autosave-styles';
        style.textContent = `
            .autosave-status {
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                z-index: 9999 !important;
                background: #10b981 !important;
                color: white !important;
                padding: 8px 16px !important;
                border-radius: 6px !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                font-size: 14px !important;
                opacity: 0 !important;
                transform: translateY(-10px) !important;
                transition: all 0.3s ease !important;
                pointer-events: none !important;
                font-family: system-ui, -apple-system, sans-serif !important;
                max-width: 300px !important;
                word-wrap: break-word !important;
            }

            .autosave-status.show {
                opacity: 1 !important;
                transform: translateY(0) !important;
            }

            .autosave-status.saving {
                background: #f59e0b !important;
            }

            .autosave-status.error {
                background: #ef4444 !important;
            }

            .autosave-status.info {
                background: #3b82f6 !important;
            }

            .autosave-status.success {
                background: #10b981 !important;
            }

            .autosave-status.draft-created {
                background: #8b5cf6 !important;
            }

            .autosave-status .spinner {
                display: inline-block !important;
                width: 12px !important;
                height: 12px !important;
                border: 2px solid transparent !important;
                border-top: 2px solid currentColor !important;
                border-radius: 50% !important;
                animation: autosave-spin 1s linear infinite !important;
                margin-right: 6px !important;
            }

            @keyframes autosave-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .autosave-status-content {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }

            .autosave-icon {
                width: 16px !important;
                height: 16px !important;
                flex-shrink: 0 !important;
            }

            .autosave-spinner {
                animation: autosave-spin 1s linear infinite !important;
            }
        `;
        document.head.appendChild(style);
        console.log('AutoSave: Status styles added to head');
        
        // Verify styles were added
        const verification = document.getElementById('autosave-styles');
        console.log('AutoSave: Styles verification:', !!verification);
    }

    attachEventListeners() {
        // Watch for changes on tracked fields
        this.options.watchedFields.forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Store initial value
                this.lastSaved[fieldName] = field.value;

                // Add event listeners
                field.addEventListener('input', () => this.onFieldChange(fieldName));
                field.addEventListener('blur', () => this.onFieldBlur(fieldName));
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    }

    onFieldChange(fieldName) {
        // Clear existing timeout and set new one for debounced save
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        // If in create mode and title field changes, create draft first
        if (this.isCreateMode && !this.isDraftCreated && fieldName === 'title') {
            this.saveTimeout = setTimeout(() => {
                this.createDraftThenSave(fieldName);
            }, this.options.debounceDelay);
        } else {
            this.saveTimeout = setTimeout(() => {
                this.saveField(fieldName);
            }, this.options.debounceDelay);
        }
    }

    onFieldBlur(fieldName) {
        // Save immediately when field loses focus
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        // If in create mode and title field blurs, create draft first
        if (this.isCreateMode && !this.isDraftCreated && fieldName === 'title') {
            this.createDraftThenSave(fieldName);
        } else {
            this.saveField(fieldName);
        }
    }

    startPeriodicSave() {
        this.intervalId = setInterval(() => {
            this.saveAllChanges();
        }, this.options.saveInterval);
    }

    async createDraftThenSave(fieldName) {
        if (this.isDestroyed) return;

        const titleField = this.form.querySelector('[name="title"]');
        if (!titleField || !titleField.value.trim()) {
            console.log('AutoSave: Title is empty, skipping draft creation');
            return;
        }

        try {
            this.showStatus('saving', 'Creating draft...');
            
            const formData = new FormData();
            formData.append('title', titleField.value.trim());
            
            // Get content type from form
            const contentTypeField = this.form.querySelector('[name="content_type"]');
            if (contentTypeField) {
                formData.append('content_type', contentTypeField.value);
            }
            
            // Get CSRF token
            const csrfToken = this.form.querySelector('[name="_token"]');
            if (csrfToken) {
                formData.append('_token', csrfToken.value);
            }

            const response = await fetch(this.options.createEndpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Draft creation failed');
            }

            // Update state to edit mode
            this.contentId = result.content_id;
            this.isDraftCreated = true;
            this.isCreateMode = false;
            this.isEnabled = true;
            
            // Update form action to edit mode
            this.form.action = `/admin/content/${this.contentId}/update`;
            
            // Update URL alias field if provided
            if (result.url_alias) {
                const urlAliasField = this.form.querySelector('[name="url_alias"]');
                if (urlAliasField && !urlAliasField.value) {
                    urlAliasField.value = result.url_alias;
                }
            }
            
            // Store initial values
            this.lastSaved[fieldName] = titleField.value;
            
            // Start periodic saves
            this.startPeriodicSave();
            
            this.showStatus('draft-created', 'Draft created - auto-save enabled');
            console.log('AutoSave: Draft created with ID:', this.contentId);
            
        } catch (error) {
            console.error('AutoSave: Failed to create draft', error);
            this.showStatus('error', 'Failed to create draft');
        }
    }

    async saveField(fieldName) {
        if (this.isDestroyed || !this.isEnabled) return;

        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        const currentValue = field.value;
        const lastValue = this.lastSaved[fieldName];

        // Only save if value has changed
        if (currentValue === lastValue) return;

        try {
            await this.performSave(fieldName, currentValue);
            this.lastSaved[fieldName] = currentValue;
        } catch (error) {
            console.error('AutoSave: Failed to save field', fieldName, error);
            this.showStatus('error', 'Auto-save failed');
        }
    }

    async saveAllChanges() {
        if (this.isDestroyed) return;

        // If in create mode and haven't created draft yet, check if title exists
        if (this.isCreateMode && !this.isDraftCreated) {
            const titleField = this.form.querySelector('[name="title"]');
            if (titleField && titleField.value.trim()) {
                await this.createDraftThenSave('title');
                return; // createDraftThenSave will handle the initial save
            }
            return; // No title yet, nothing to save
        }

        if (!this.isEnabled) return;

        const changedFields = [];
        
        this.options.watchedFields.forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field && field.value !== this.lastSaved[fieldName]) {
                changedFields.push({ name: fieldName, value: field.value });
            }
        });

        if (changedFields.length === 0) return;

        try {
            for (const fieldData of changedFields) {
                await this.performSave(fieldData.name, fieldData.value);
                this.lastSaved[fieldData.name] = fieldData.value;
            }
        } catch (error) {
            console.error('AutoSave: Failed to save changes', error);
            this.showStatus('error', 'Auto-save failed');
        }
    }

    async performSave(field, value) {
        if (!this.contentId) {
            throw new Error('No content ID available for saving');
        }

        this.showStatus('saving', 'Saving...');

        const formData = new FormData();
        formData.append('id', this.contentId);
        formData.append('field', field);
        formData.append('value', value);
        
        // Get CSRF token
        const csrfToken = this.form.querySelector('[name="_token"]');
        if (csrfToken) {
            formData.append('_token', csrfToken.value);
        }

        const response = await fetch(this.options.endpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Save failed');
        }

        this.showStatus('success', `Saved at ${result.timestamp || new Date().toLocaleTimeString()}`);
        return result;
    }

    showStatus(type, message) {
        console.log('AutoSave: Showing status -', type, ':', message);
        
        const status = document.getElementById('autosave-status');
        if (!status) {
            console.warn('AutoSave: Status element not found, recreating...');
            this.createStatusIndicator();
            return this.showStatus(type, message);
        }

        // Remove all existing type classes
        status.className = 'autosave-status show';
        
        // Add new type class
        status.classList.add(type);
        
        console.log('AutoSave: Status element classes:', status.className);

        // Create enhanced content with spinner for saving state
        if (type === 'saving') {
            status.innerHTML = `
                <div class="autosave-status-content">
                    <div class="spinner"></div>
                    <span>${message}</span>
                </div>
            `;
        } else if (type === 'draft-created') {
            status.innerHTML = `
                <div class="autosave-status-content">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        } else if (type === 'success') {
            status.innerHTML = `
                <div class="autosave-status-content">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        } else if (type === 'error') {
            status.innerHTML = `
                <div class="autosave-status-content">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        } else {
            status.innerHTML = `
                <div class="autosave-status-content">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
        }

        // Auto-hide success messages after 3 seconds
        if (type === 'success' || type === 'draft-created') {
            setTimeout(() => {
                if (status.classList.contains(type)) {
                    status.classList.remove('show');
                }
            }, 3000);
        }

        // Auto-hide after 3 seconds for success/error
        if (type !== 'saving') {
            setTimeout(() => {
                if (status.classList.contains(type)) {
                    status.classList.remove('show');
                }
            }, 3000);
        }
    }

    hasUnsavedChanges() {
        // If in create mode and no draft created yet, check if there's any content
        if (this.isCreateMode && !this.isDraftCreated) {
            return this.options.watchedFields.some(fieldName => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                return field && field.value.trim();
            });
        }

        // Normal check for saved vs current values
        return this.options.watchedFields.some(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            return field && field.value !== this.lastSaved[fieldName];
        });
    }

    destroy() {
        this.isDestroyed = true;
        this.isEnabled = false;

        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        // Remove status indicator
        const status = document.getElementById('autosave-status');
        if (status) {
            status.remove();
        }

        // Remove styles
        const styles = document.getElementById('autosave-styles');
        if (styles) {
            styles.remove();
        }
    }

    // Public methods for manual control
    enable() {
        this.isEnabled = true;
        this.showStatus('success', 'Auto-save enabled');
    }

    disable() {
        this.isEnabled = false;
        this.showStatus('error', 'Auto-save disabled');
    }

    saveNow() {
        return this.saveAllChanges();
    }
}

// Initialize auto-save when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('AutoSave: DOM loaded, checking for content form...');
    
    // Check if we're on a content edit page
    const contentForm = document.getElementById('contentForm');
    console.log('AutoSave: Content form found:', !!contentForm);
    
    if (contentForm) {
        console.log('AutoSave: Creating AutoSave instance...');
        try {
            window.autoSave = new AutoSave('contentForm');
            console.log('AutoSave: Instance created successfully:', !!window.autoSave);
            
            // Force show an initial status to verify visibility
            setTimeout(() => {
                if (window.autoSave && typeof window.autoSave.showStatus === 'function') {
                    console.log('AutoSave: Triggering initial status display...');
                    // The showStatus call from init() should already be active
                }
            }, 500);
            
        } catch (error) {
            console.error('AutoSave: Failed to create instance:', error);
        }
    } else {
        console.log('AutoSave: No content form found, auto-save not initialized');
    }
});