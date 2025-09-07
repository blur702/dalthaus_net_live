/**
 * SINGLE TinyMCE Initialization File
 * This is the ONLY place where TinyMCE is defined and initialized
 * Only loaded on pages that need TinyMCE
 */

// Prevent duplicate loading
if (!window.TINYMCE_LOADED) {
    window.TINYMCE_LOADED = true;
    
    // Load and initialize TinyMCE
    (function() {
        let initialized = false;
        let scriptLoaded = false;
        
        function loadTinyMCE() {
            if (scriptLoaded) return Promise.resolve();
            
            return new Promise((resolve, reject) => {
                // Check if already loaded
                if (typeof tinymce !== 'undefined') {
                    scriptLoaded = true;
                    resolve();
                    return;
                }
                
                // Create script tag
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js';
                script.referrerPolicy = 'origin';
                
                script.onload = () => {
                    scriptLoaded = true;
                    setTimeout(resolve, 100);
                };
                
                script.onerror = () => reject(new Error('Failed to load TinyMCE'));
                document.head.appendChild(script);
            });
        }
        
        function initTinyMCE() {
            if (initialized) return;
            
            // Find textareas that need TinyMCE
            const targets = document.querySelectorAll(
                'textarea#body, textarea.tinymce-editor, textarea[data-tinymce="true"]'
            );
            
            if (targets.length === 0) return;
            
            loadTinyMCE().then(() => {
                if (typeof tinymce === 'undefined') return;
                
                // Remove any existing instances
                try {
                    tinymce.remove();
                } catch (e) {}
                
                // Initialize
                tinymce.init({
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
                            console.log('TinyMCE ready:', editor.id);
                            
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
                });
                
                initialized = true;
                console.log('TinyMCE initialization complete');
            }).catch(err => {
                console.error('Failed to initialize TinyMCE:', err);
            });
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTinyMCE);
        } else {
            initTinyMCE();
        }
    })();
}