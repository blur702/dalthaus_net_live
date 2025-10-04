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
        this.createStatusIndicator();
        this.attachEventListeners();

        if (this.contentId) {
            // Edit mode - enable auto-save immediately
            this.isEnabled = true;
            this.startPeriodicSave();
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
    }

    createStatusIndicator() {
        // Create status indicator if it doesn't exist
        if (document.getElementById('autosave-status')) return;

        const statusDiv = document.createElement('div');
        statusDiv.id = 'autosave-status';
        statusDiv.className = 'autosave-status';
        statusDiv.innerHTML = `
            <div class="autosave-status-content">
                <svg class="autosave-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span class="autosave-text">Auto-save enabled</span>
            </div>
        `;

        // Insert status indicator near the form buttons
        const buttonContainer = this.form.querySelector('.flex.items-center.justify-end');
        if (buttonContainer) {
            buttonContainer.parentNode.insertBefore(statusDiv, buttonContainer);
        } else {
            this.form.appendChild(statusDiv);
        }

        // Add CSS styles
        this.addStatusStyles();
    }

    addStatusStyles() {
        if (document.getElementById('autosave-styles')) return;

        const style = document.createElement('style');
        style.id = 'autosave-styles';
        style.textContent = `
            .autosave-status {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                background: #10b981;
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                font-size: 14px;
                opacity: 0;
                transform: translateY(-10px);
                transition: all 0.3s ease;
                pointer-events: none;
            }

            .autosave-status.show {
                opacity: 1;
                transform: translateY(0);
            }

            .autosave-status.saving {
                background: #f59e0b;
            }

            .autosave-status.error {
                background: #ef4444;
            }

            .autosave-status.info {
                background: #3b82f6;
            }

            .autosave-status-content {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .autosave-icon {
                width: 16px;
                height: 16px;
            }

            .autosave-spinner {
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
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
            
            this.showStatus('success', 'Draft created - auto-save enabled');
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
        const status = document.getElementById('autosave-status');
        if (!status) return;

        const textElement = status.querySelector('.autosave-text');
        const iconElement = status.querySelector('.autosave-icon');

        // Update content
        textElement.textContent = message;

        // Update icon based on status
        if (type === 'saving') {
            iconElement.innerHTML = `<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
                                   <path d="M4 12a8 8 0 018-8V2.5a1.5 1.5 0 011 1.415L14 12" stroke="currentColor" stroke-width="4" fill="none"/>`;
            iconElement.classList.add('autosave-spinner');
        } else if (type === 'error') {
            iconElement.innerHTML = `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L10 10.586l2.707-2.707a1 1 0 011.414 1.414L11.414 12l2.707 2.707a1 1 0 01-1.414 1.414L10 13.414l-2.707 2.707a1 1 0 01-1.414-1.414L8.586 12 5.879 9.293a1 1 0 011.414-1.414L10 10.586z" clip-rule="evenodd"/>`;
            iconElement.classList.remove('autosave-spinner');
        } else if (type === 'info') {
            iconElement.innerHTML = `<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>`;
            iconElement.classList.remove('autosave-spinner');
        } else {
            iconElement.innerHTML = `<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>`;
            iconElement.classList.remove('autosave-spinner');
        }

        // Update styling
        status.className = `autosave-status show ${type}`;

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
    // Check if we're on a content edit page
    const contentForm = document.getElementById('contentForm');
    if (contentForm) {
        window.autoSave = new AutoSave('contentForm');
    }
});