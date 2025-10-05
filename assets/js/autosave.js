/**
 * Auto-save functionality with minimalistic UI design
 * Subtle, non-obtrusive indicators for content autosaving
 */

class AutoSave {
    constructor(formId, options = {}) {
        console.log('AutoSave: Initializing with minimalistic design');
        
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
            loadEndpoint: '/admin/content/load-autosave',
            listEndpoint: '/admin/content/list-autosaves',
            watchedFields: ['title', 'teaser', 'body'],
            ...options
        };

        // State
        this.autosaveUUID = null;
        this.contentId = null;
        this.isEnabled = false;
        this.lastSaved = {};
        this.saveTimeout = null;
        this.intervalId = null;
        this.isDestroyed = false;
        
        // Initialize
        this.init();
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0,
                v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    init() {
        // Get or generate autosave UUID
        const uuidField = this.form.querySelector('#autosave_uuid');
        if (uuidField) {
            if (!uuidField.value) {
                this.autosaveUUID = this.generateUUID();
                uuidField.value = this.autosaveUUID;
            } else {
                this.autosaveUUID = uuidField.value;
            }
        }
        
        // Get content ID if editing
        const contentIdField = this.form.querySelector('#content_id');
        if (contentIdField && contentIdField.value) {
            this.contentId = parseInt(contentIdField.value);
        }
        
        this.createStatusIndicator();
        this.attachEventListeners();
        this.setupAutosaveRecovery();
        
        // Enable autosave only after title is entered
        const titleField = this.form.querySelector('[name="title"]');
        if (titleField && titleField.value.trim()) {
            this.enable();
        } else {
            this.showStatus('neutral', '');
        }
    }

    setupAutosaveRecovery() {
        const loadBtn = document.getElementById('loadAutosave');
        if (loadBtn) {
            loadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadAutosaveData();
            });
        }
        
        const dismissBtn = document.getElementById('dismissAutosave');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', (e) => {
                e.preventDefault();
                dismissBtn.closest('.bg-blue-50').style.display = 'none';
            });
        }
    }

    async loadAutosaveData() {
        if (!window.autosaveData) return;
        
        try {
            const data = window.autosaveData;
            
            const titleField = this.form.querySelector('[name="title"]');
            if (titleField && data.title) {
                titleField.value = data.title;
            }
            
            const bodyField = this.form.querySelector('[name="body"]');
            if (bodyField && data.content) {
                bodyField.value = data.content;
            }
            
            const teaserField = this.form.querySelector('[name="teaser"]');
            if (teaserField && data.excerpt) {
                teaserField.value = data.excerpt;
            }
            
            const notification = document.querySelector('.bg-blue-50');
            if (notification) {
                notification.style.display = 'none';
            }
            
            this.showStatus('success', 'Restored');
            
            this.options.watchedFields.forEach(fieldName => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    this.lastSaved[fieldName] = field.value;
                }
            });
            
        } catch (error) {
            console.error('AutoSave: Failed to load autosave data', error);
            this.showStatus('error', 'Failed');
        }
    }

    createStatusIndicator() {
        // Use existing inline autosaveStatus element if present
        const existingStatus = document.getElementById('autosaveStatus');
        if (existingStatus) {
            this.statusElement = existingStatus;
            // Apply minimalistic styles
            existingStatus.style.cssText = 'font-size: 11px; color: #6b7280; opacity: 0.8;';
        } else {
            // Create minimal floating indicator as fallback
            const statusDiv = document.createElement('div');
            statusDiv.id = 'autosave-status';
            statusDiv.className = 'autosave-status';
            document.body.appendChild(statusDiv);
            this.statusElement = statusDiv;
        }
        
        // Add minimalistic CSS styles
        this.addStatusStyles();
    }

    addStatusStyles() {
        const existing = document.getElementById('autosave-styles');
        if (existing) return;

        const style = document.createElement('style');
        style.id = 'autosave-styles';
        style.textContent = `
            /* Minimalistic autosave indicator */
            .autosave-indicator {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 11px;
                color: #6b7280;
                opacity: 0.8;
                transition: opacity 0.15s ease;
            }
            
            .autosave-indicator:hover {
                opacity: 1;
            }
            
            /* Small status dot */
            .autosave-dot {
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: #9ca3af;
                transition: background 0.2s ease, transform 0.2s ease;
            }
            
            .autosave-dot.saving {
                background: #f59e0b;
                animation: subtle-pulse 2s ease-in-out infinite;
            }
            
            .autosave-dot.success {
                background: #10b981;
            }
            
            .autosave-dot.error {
                background: #ef4444;
            }
            
            .autosave-dot.neutral {
                background: #9ca3af;
            }
            
            @keyframes subtle-pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(0.8); }
            }
            
            /* Fallback floating indicator (minimal) */
            .autosave-status {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                padding: 6px 10px;
                border-radius: 4px;
                font-size: 11px;
                color: #6b7280;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(0, 0, 0, 0.05);
                opacity: 0;
                transform: translateY(4px);
                transition: all 0.15s ease;
                pointer-events: none;
                z-index: 50;
            }
            
            .autosave-status.show {
                opacity: 0.9;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(style);
    }

    attachEventListeners() {
        this.options.watchedFields.forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Store initial value
                this.lastSaved[fieldName] = field.value;

                // Add event listeners
                field.addEventListener('input', () => this.onFieldChange(fieldName));
                field.addEventListener('blur', () => this.onFieldBlur(fieldName));
                
                // Special handling for title field
                if (fieldName === 'title') {
                    field.addEventListener('input', () => {
                        if (field.value.trim() && !this.isEnabled) {
                            this.enable();
                        } else if (!field.value.trim() && this.isEnabled) {
                            this.disable();
                        }
                    });
                }
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes.';
                return e.returnValue;
            }
        });
    }

    onFieldChange(fieldName) {
        if (!this.isEnabled) return;
        
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        // Show subtle saving indicator after a brief delay
        this.saveTimeout = setTimeout(() => {
            this.saveAllChanges();
        }, this.options.debounceDelay);
    }

    onFieldBlur(fieldName) {
        if (!this.isEnabled) return;
        
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveAllChanges();
    }

    startPeriodicSave() {
        this.intervalId = setInterval(() => {
            this.saveAllChanges();
        }, this.options.saveInterval);
    }

    stopPeriodicSave() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    async saveAllChanges() {
        if (this.isDestroyed || !this.isEnabled) return;

        const titleField = this.form.querySelector('[name="title"]');
        if (!titleField || !titleField.value.trim()) return;

        try {
            this.showStatus('saving', 'Saving');
            
            const formData = new FormData();
            formData.append('autosave_uuid', this.autosaveUUID);
            
            if (this.contentId) {
                formData.append('content_id', this.contentId);
            }
            
            this.options.watchedFields.forEach(fieldName => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    formData.append(fieldName === 'body' ? 'body' : fieldName, field.value);
                }
            });
            
            const contentTypeField = this.form.querySelector('[name="content_type"]');
            if (contentTypeField) {
                formData.append('content_type', contentTypeField.value);
            }
            
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

            this.options.watchedFields.forEach(fieldName => {
                const field = this.form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    this.lastSaved[fieldName] = field.value;
                }
            });

            const now = new Date();
            const time = now.toLocaleTimeString([], { 
                hour: 'numeric', 
                minute: '2-digit'
            });
            
            this.showStatus('success', time);
            
        } catch (error) {
            console.error('AutoSave: Failed', error);
            this.showStatus('error', 'Failed');
        }
    }

    showStatus(type, message) {
        if (!this.statusElement) return;
        
        // For inline status element
        if (this.statusElement.id === 'autosaveStatus') {
            if (!message) {
                this.statusElement.innerHTML = '';
                return;
            }
            
            const dotClass = type === 'saving' ? 'saving' :
                           type === 'success' ? 'success' :
                           type === 'error' ? 'error' :
                           'neutral';
            
            this.statusElement.innerHTML = `
                <span class="autosave-indicator">
                    <span class="autosave-dot ${dotClass}"></span>
                    <span>${message}</span>
                </span>
            `;
        } else {
            // Fallback floating indicator
            if (!message) {
                this.statusElement.classList.remove('show');
                return;
            }
            
            this.statusElement.textContent = message;
            this.statusElement.className = 'autosave-status show';
            
            // Auto-hide after 2 seconds
            if (type !== 'saving') {
                setTimeout(() => {
                    this.statusElement.classList.remove('show');
                }, 2000);
            }
        }
    }

    hasUnsavedChanges() {
        return this.options.watchedFields.some(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            return field && field.value !== this.lastSaved[fieldName];
        });
    }

    enable() {
        if (this.isEnabled) return;
        
        this.isEnabled = true;
        this.startPeriodicSave();
        this.showStatus('success', 'Active');
        
        // Clear "Active" message after 1.5 seconds
        setTimeout(() => {
            if (this.isEnabled) {
                this.showStatus('neutral', '');
            }
        }, 1500);
    }

    disable() {
        if (!this.isEnabled) return;
        
        this.isEnabled = false;
        this.stopPeriodicSave();
        this.showStatus('neutral', '');
    }

    destroy() {
        this.isDestroyed = true;
        this.isEnabled = false;

        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        this.stopPeriodicSave();

        const floatingStatus = document.getElementById('autosave-status');
        if (floatingStatus) {
            floatingStatus.remove();
        }

        const styles = document.getElementById('autosave-styles');
        if (styles) {
            styles.remove();
        }
    }
}

// Initialize when DOM is ready
function initializeAutoSave() {
    const contentForm = document.getElementById('contentForm');
    if (contentForm) {
        window.autoSave = new AutoSave('contentForm');
        window.autoSaveInstance = window.autoSave;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAutoSave);
} else {
    initializeAutoSave();
}