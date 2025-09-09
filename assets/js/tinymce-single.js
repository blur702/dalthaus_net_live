/**
 * SINGLE TinyMCE Initialization File
 * This is the ONLY place where TinyMCE is defined and initialized
 * Only loaded on pages that need TinyMCE
 * 
 * Fixes custom element registration errors by:
 * 1. Checking if TinyMCE script is already loaded
 * 2. Checking if custom elements are already registered
 * 3. Using proper initialization flags to prevent race conditions
 */

(function() {
    'use strict';
    
    // Global flags to prevent duplicate initialization
    window.TINYMCE_STATE = window.TINYMCE_STATE || {
        scriptLoaded: false,
        initialized: false,
        initInProgress: false,
        customElementsRegistered: false
    };
    
    const state = window.TINYMCE_STATE;
    
    function loadTinyMCE() {
        if (state.scriptLoaded) {
            return Promise.resolve();
        }
        
        return new Promise((resolve, reject) => {
            // Check if TinyMCE is already available
            if (typeof tinymce !== 'undefined') {
                state.scriptLoaded = true;
                resolve();
                return;
            }
            
            // Check if script is already in DOM
            const existingScript = document.querySelector('script[src*="tinymce"]');
            if (existingScript) {
                // Wait for TinyMCE to be available
                const waitForTinyMCE = () => {
                    if (typeof tinymce !== 'undefined') {
                        state.scriptLoaded = true;
                        resolve();
                    } else {
                        setTimeout(waitForTinyMCE, 50);
                    }
                };
                waitForTinyMCE();
                return;
            }
            
            // Load TinyMCE script
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
            script.referrerPolicy = 'origin';
            
            script.onload = () => {
                state.scriptLoaded = true;
                // Give TinyMCE time to initialize
                setTimeout(resolve, 100);
            };
            
            script.onerror = () => {
                reject(new Error('Failed to load TinyMCE'));
            };
            
            document.head.appendChild(script);
        });
    }
    
    function getEditorConfig() {
        return {
            selector: 'textarea#body, textarea.tinymce-editor, textarea[data-tinymce="true"]',
            height: 500,
            menubar: false,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount pagebreak',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image | pagebreak code',
            images_upload_url: '/admin/upload/tinymce',
            automatic_uploads: true,
            images_reuse_filename: true,
            browser_spellcheck: true,
            gecko_spellcheck: false,
            contextmenu: false,
            inline: false,
            promotion: false,
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('TinyMCE editor initialized:', editor.id);
                    
                    // Auto-save on form submit
                    const form = editor.getElement().form;
                    if (form && !form.dataset.tinymceHandler) {
                        form.dataset.tinymceHandler = 'true';
                        form.addEventListener('submit', function(e) {
                            // Save TinyMCE content to textarea before submit
                            if (typeof tinymce !== 'undefined' && tinymce.triggerSave) {
                                tinymce.triggerSave();
                            }
                            // Make sure the textarea has content
                            const textarea = document.getElementById('body');
                            if (textarea && editor.getContent) {
                                textarea.value = editor.getContent();
                            }
                        });
                    }
                });
            }
        };
    }
    
    function initTinyMCE() {
        // Skip if already initialized or in progress
        if (state.initialized || state.initInProgress) {
            console.log('TinyMCE initialization already completed or in progress');
            return;
        }
        
        // Find textareas that need TinyMCE
        const targets = document.querySelectorAll(
            'textarea#body, textarea.tinymce-editor, textarea[data-tinymce="true"]'
        );
        
        if (targets.length === 0) {
            return;
        }
        
        // Set flag to prevent concurrent initialization
        state.initInProgress = true;
        
        loadTinyMCE().then(() => {
            if (typeof tinymce === 'undefined') {
                state.initInProgress = false;
                return;
            }
            
            // Check if custom elements are already registered
            // This prevents the "custom element already defined" error
            const customElementsExist = customElements.get('mce-autosize-textarea');
            
            if (customElementsExist && !state.customElementsRegistered) {
                // Mark that custom elements have been registered
                state.customElementsRegistered = true;
                console.log('TinyMCE custom elements detected as already registered');
            }
            
            // Remove any existing editor instances
            try {
                if (tinymce.get().length > 0) {
                    console.log('Removing existing TinyMCE instances');
                    tinymce.remove();
                }
            } catch (e) {
                console.error('Error removing TinyMCE instances:', e);
            }
            
            // Initialize TinyMCE
            try {
                tinymce.init(getEditorConfig()).then(() => {
                    state.initialized = true;
                    state.initInProgress = false;
                    state.customElementsRegistered = true;
                    console.log('TinyMCE initialization complete');
                }).catch(err => {
                    // If we get a custom element error, it means TinyMCE is already loaded
                    // Just mark as initialized and continue
                    if (err.message && err.message.includes('already been defined')) {
                        state.initialized = true;
                        state.customElementsRegistered = true;
                        console.log('TinyMCE already initialized (custom elements exist)');
                    } else {
                        console.error('TinyMCE initialization error:', err);
                    }
                    state.initInProgress = false;
                });
            } catch (err) {
                // Catch synchronous errors
                if (err.message && err.message.includes('already been defined')) {
                    state.initialized = true;
                    state.customElementsRegistered = true;
                    console.log('TinyMCE already initialized (caught sync error)');
                } else {
                    console.error('Failed to initialize TinyMCE:', err);
                }
                state.initInProgress = false;
            }
        }).catch(err => {
            console.error('Failed to load TinyMCE:', err);
            state.initInProgress = false;
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTinyMCE);
    } else {
        // DOM is already loaded
        initTinyMCE();
    }
    
    // Also handle dynamic content that might be loaded later
    // This prevents errors when navigating between pages without full reload
    window.addEventListener('load', () => {
        setTimeout(initTinyMCE, 100);
    });
})();