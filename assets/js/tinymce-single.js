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
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image dualimage modalimage testbutton | pagebreak code',
            pagebreak_separator: '<!-- pagebreak -->',
            images_upload_url: '/admin/upload/tinymce',
            automatic_uploads: true,
            images_reuse_filename: true,
            browser_spellcheck: true,
            gecko_spellcheck: false,
            contextmenu: false,
            inline: false,
            promotion: false,
            branding: false,
            // Fix image display issues
            relative_urls: false,
            remove_script_host: false,
            document_base_url: window.location.origin + '/',
            // Ensure images load properly in editor
            verify_html: false,
            // Image handling options
            image_advtab: true,
            image_caption: true,
            image_title: true,
            setup: function(editor) {
                console.log('TinyMCE setup function called for editor:', editor.id);
                
                // Add custom dual image button - using a different approach
                console.log('About to register dual image button...');
                
                try {
                    // Register the dual image button with an icon
                    editor.ui.registry.addButton('dualimage', {
                        text: 'Modal Image',
                        tooltip: 'Insert image with modal view',
                        icon: 'image',
                        onAction: function() {
                            console.log('Dual image button clicked');
                            if (typeof window.showDualImageDialog === 'function') {
                                window.showDualImageDialog(editor);
                            } else if (typeof showDualImageDialog === 'function') {
                                showDualImageDialog(editor);
                            } else {
                                console.error('showDualImageDialog function not found');
                                alert('Dual image functionality not available');
                            }
                        }
                    });
                    console.log('✅ Dual image button registered successfully');
                } catch (error) {
                    console.error('❌ Failed to register dual image button:', error);
                }
                
                // Add modalimage button as an alternative
                try {
                    editor.ui.registry.addButton('modalimage', {
                        text: 'Modal Image',
                        tooltip: 'Insert image with modal view (alternative)',
                        onAction: function() {
                            console.log('Modal image button clicked');
                            if (typeof window.showDualImageDialog === 'function') {
                                window.showDualImageDialog(editor);
                            } else {
                                alert('Modal image functionality not available');
                            }
                        }
                    });
                    console.log('✅ Modal image button registered successfully');
                } catch (error) {
                    console.error('❌ Failed to register modal image button:', error);
                }

                // Let's also try adding it with a different name to test
                try {
                    editor.ui.registry.addButton('testbutton', {
                        text: 'TEST',
                        tooltip: 'Test button',
                        onAction: function() {
                            console.log('Test button clicked');
                            alert('Test button works!');
                        }
                    });
                    console.log('✅ Test button registered successfully');
                } catch (error) {
                    console.error('❌ Failed to register test button:', error);
                }

                editor.on('init', function() {
                    console.log('TinyMCE editor initialized:', editor.id);
                    
                    // Manually add dual image button to toolbar after initialization
                    setTimeout(() => {
                        const container = editor.getContainer();
                        console.log('Editor container found, attempting to add dual image button manually');
                        
                        // Try different toolbar selectors
                        let toolbar = container.querySelector('.tox-toolbar');
                        if (!toolbar) toolbar = container.querySelector('.tox-toolbar-primary');
                        if (!toolbar) toolbar = container.querySelector('.mce-toolbar');
                        if (!toolbar) toolbar = container.querySelector('[role="toolbar"]');
                        
                        if (toolbar) {
                            console.log('✅ Toolbar found, attempting to inject dual image button');
                            
                            // Find the image button to insert our button after it
                            let imageButton = toolbar.querySelector('button[title*="Insert"], button[aria-label*="Insert"]');
                            if (!imageButton) {
                                // Try different selectors for the image button
                                imageButton = toolbar.querySelector('button[title*="Image"], button[aria-label*="Image"]');
                            }
                            if (!imageButton) {
                                // Look for any button that might be the image button by icon or content
                                const buttons = toolbar.querySelectorAll('button');
                                imageButton = Array.from(buttons).find(btn => 
                                    btn.innerHTML.includes('image') || 
                                    btn.innerHTML.includes('Image') ||
                                    btn.title.toLowerCase().includes('image') ||
                                    btn.getAttribute('aria-label')?.toLowerCase().includes('image')
                                );
                            }
                            if (!imageButton && toolbar.querySelectorAll('button').length > 5) {
                                // Fallback: just use any button in the middle of the toolbar
                                const buttons = toolbar.querySelectorAll('button');
                                imageButton = buttons[Math.floor(buttons.length / 2)];
                                console.log('Using fallback button position');
                            }
                            
                            if (imageButton) {
                                console.log('Found image button, creating dual image button');
                                
                                // Create the dual image button manually
                                const dualImageBtn = document.createElement('button');
                                dualImageBtn.type = 'button';
                                dualImageBtn.textContent = '🖼️📱';
                                dualImageBtn.title = 'Insert image with modal view';
                                dualImageBtn.setAttribute('aria-label', 'Insert image with modal view');
                                dualImageBtn.className = imageButton.className; // Copy styling from existing button
                                dualImageBtn.style.marginLeft = '4px';
                                
                                // Add click handler
                                dualImageBtn.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    console.log('Manual dual image button clicked');
                                    if (typeof showDualImageDialog === 'function') {
                                        showDualImageDialog(editor);
                                    } else {
                                        alert('Dual image functionality not available');
                                    }
                                });
                                
                                // Insert after the image button
                                imageButton.parentNode.insertBefore(dualImageBtn, imageButton.nextSibling);
                                console.log('✅ Dual image button manually added to toolbar');
                                
                            } else {
                                console.log('❌ Could not find image button to position dual image button');
                            }
                            
                        } else {
                            console.log('❌ No toolbar found for manual button injection');
                            console.log('Container HTML preview:', container.innerHTML.substring(0, 300));
                        }
                    }, 1000); // Wait longer for toolbar to fully render
                    
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

                // Add modal functionality to images when they are inserted
                editor.on('NodeChange', function(e) {
                    addModalToNewImages(editor);
                });

                // Add modal functionality after content is set
                editor.on('SetContent', function(e) {
                    setTimeout(() => addModalToNewImages(editor), 100);
                });

                // Function to add modal functionality to images in the editor that have data-modal-src
                function addModalToNewImages(editor) {
                    const editorBody = editor.getBody();
                    if (!editorBody) return;

                    const images = editorBody.querySelectorAll('img[data-modal-src]');
                    images.forEach(function(img) {
                        // Skip if already has modal functionality
                        if (img.hasAttribute('data-modal-enabled') || img.onclick) {
                            return;
                        }

                        // Get the modal image source from data attribute
                        const modalSrc = img.getAttribute('data-modal-src');
                        if (!modalSrc) {
                            return; // No modal image specified
                        }

                        // Mark as having modal functionality
                        img.setAttribute('data-modal-enabled', 'true');
                        
                        // Add onclick handler for modal using the modal image source
                        const alt = img.alt || 'Image';
                        img.setAttribute('onclick', `openImageModal('${modalSrc}', '${alt}')`);
                        
                        // Add styling
                        img.style.cursor = 'pointer';
                        if (!img.classList.contains('modal-image')) {
                            img.classList.add('modal-image');
                        }
                    });
                }

                // Add CSS for dual image dialog
                if (!document.getElementById('dual-image-styles')) {
                    const style = document.createElement('style');
                    style.id = 'dual-image-styles';
                    style.textContent = `
                        .dual-image-dialog {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            z-index: 9999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .dual-image-overlay {
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0, 0, 0, 0.5);
                            cursor: pointer;
                        }
                        .dual-image-content {
                            position: relative;
                            background: white;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                            max-width: 500px;
                            width: 90%;
                            max-height: 90%;
                            overflow-y: auto;
                        }
                        .dual-image-header {
                            padding: 20px 20px 10px;
                            border-bottom: 1px solid #eee;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }
                        .dual-image-header h3 {
                            margin: 0;
                            font-size: 18px;
                            color: #333;
                        }
                        .close-btn {
                            background: none;
                            border: none;
                            font-size: 24px;
                            cursor: pointer;
                            color: #666;
                            padding: 0;
                            width: 30px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .close-btn:hover {
                            color: #333;
                        }
                        .dual-image-body {
                            padding: 20px;
                        }
                        .form-group {
                            margin-bottom: 15px;
                        }
                        .form-group label {
                            display: block;
                            margin-bottom: 5px;
                            font-weight: bold;
                            color: #333;
                        }
                        .form-group input[type="file"],
                        .form-group input[type="text"],
                        .form-group input[type="number"] {
                            width: 100%;
                            padding: 8px 12px;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                            box-sizing: border-box;
                        }
                        .form-group small {
                            display: block;
                            margin-top: 5px;
                            color: #666;
                            font-size: 12px;
                        }
                        .dual-image-body button[type="submit"] {
                            background: #007cba;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                            width: 100%;
                        }
                        .dual-image-body button[type="submit"]:hover {
                            background: #005a87;
                        }
                        .dual-image-body button[type="submit"]:disabled {
                            background: #ccc;
                            cursor: not-allowed;
                        }
                    `;
                    document.head.appendChild(style);
                }

                // Global function to show dual image dialog
                window.showDualImageDialog = function(editor) {
                    const dialog = document.createElement('div');
                    dialog.className = 'dual-image-dialog';
                    dialog.innerHTML = `
                        <div class="dual-image-overlay" onclick="closeDualImageDialog()"></div>
                        <div class="dual-image-content">
                            <div class="dual-image-header">
                                <h3>Insert Image with Modal View</h3>
                                <button onclick="closeDualImageDialog()" class="close-btn">&times;</button>
                            </div>
                            <div class="dual-image-body">
                                <form id="dualImageForm" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="displayImage">Display Image (shown in content):</label>
                                        <input type="file" name="display_image" accept="image/*" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="modalImage">Modal Image (shown when clicked, optional):</label>
                                        <input type="file" name="modal_image" accept="image/*">
                                        <small>If not provided, display image will be used for modal</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="altText">Alt Text:</label>
                                        <input type="text" id="altText" placeholder="Image description">
                                    </div>
                                    <div class="form-group">
                                        <label for="imageWidth">Width (optional):</label>
                                        <input type="number" id="imageWidth" placeholder="e.g. 600">
                                    </div>
                                    <button type="submit">Upload and Insert</button>
                                </form>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(dialog);
                    
                    // Handle form submission
                    document.getElementById('dualImageForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        uploadDualImage(editor);
                    });
                };

                // Global function to close dual image dialog
                window.closeDualImageDialog = function() {
                    const dialog = document.querySelector('.dual-image-dialog');
                    if (dialog) {
                        dialog.remove();
                    }
                };

                // Global function to upload dual image
                window.uploadDualImage = function(editor) {
                    const form = document.getElementById('dualImageForm');
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    
                    submitBtn.textContent = 'Uploading...';
                    submitBtn.disabled = true;
                    
                    fetch('/admin/upload/dual-image', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.images) {
                            const displayImage = data.images.display_image;
                            const modalImage = data.images.modal_image || displayImage;
                            const altText = document.getElementById('altText').value || '';
                            const width = document.getElementById('imageWidth').value;

                            // Create unique ID for modal functionality
                            const imageId = 'img_' + Date.now();

                            // Build image HTML with modal functionality
                            let imageHtml = `<img src="${displayImage}" alt="${altText}" id="${imageId}" class="clickable-image"`;
                            if (width) {
                                imageHtml += ` width="${width}"`;
                            }
                            imageHtml += ` data-modal-src="${modalImage}"`;
                            imageHtml += ` onclick="openImageModal('${modalImage}', '${altText}')" style="cursor: pointer;">`;

                            // Insert into editor
                            editor.insertContent(imageHtml);
                            closeDualImageDialog();
                        } else {
                            alert('Upload failed: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        alert('Upload failed: ' + error.message);
                    })
                    .finally(() => {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
                };
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
            
            // Register the dual image button globally BEFORE initialization
            console.log('Registering dual image button globally before init');
            
            // Make sure showDualImageDialog is available globally
            if (typeof window.showDualImageDialog !== 'function') {
                console.log('Creating global showDualImageDialog function');
                // We'll define this function after TinyMCE setup
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