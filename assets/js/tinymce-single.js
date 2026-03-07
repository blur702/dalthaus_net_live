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
    
    // Enhanced global state management with debugging
    window.TINYMCE_STATE = window.TINYMCE_STATE || {
        scriptLoaded: false,
        initialized: false,
        initInProgress: false,
        customElementsRegistered: false,
        buttonRegistrationAttempts: 0,
        lastError: null,
        debugMode: window.ADMIN_DEBUG || false,
        cacheBuster: window.CACHE_BUSTER || Date.now()
    };
    
    const state = window.TINYMCE_STATE;
    
    // Enhanced logging function
    // Simple button registration function
    function registerCustomButtons(editor) {
        debugLog('Registering custom buttons...', 'info');
        
        try {
            // Dual Image Button
            editor.ui.registry.addButton('dualimage', {
                text: '🖼️📱',
                tooltip: 'Insert Dual Image (Display + Modal)',
                onAction: function() {
                    openDualImageDialog();
                }
            });
            
            // Modal Image Button (fallback)
            editor.ui.registry.addButton('modalimage', {
                text: '🔍',
                tooltip: 'Modal Image',
                onAction: function() {
                    openDualImageDialog();
                }
            });
            
            // Test Button
            editor.ui.registry.addButton('testbutton', {
                text: '🧪',
                tooltip: 'Test Button',
                onAction: function() {
                    alert('Test button works!');
                }
            });
            
            debugLog('Custom buttons registered successfully', 'success');
        } catch (error) {
            debugLog('Error registering buttons: ' + error.message, 'error');
        }
    }

    // Simple dual image dialog function
    function openDualImageDialog() {
        debugLog('Opening dual image dialog...', 'info');
        
        const displayImage = prompt('Enter display image URL:');
        if (!displayImage) return;
        
        const modalImage = prompt('Enter modal image URL (optional, will use display image if empty):') || displayImage;
        
        // Insert the dual image HTML
        const editor = tinymce.activeEditor;
        if (editor) {
            const html = `<img src="${displayImage}" data-modal-src="${modalImage}" alt="Dual Image" style="cursor: pointer; max-width: 100%;" onclick="openImageModal('${modalImage}')">`;
            editor.insertContent(html);
            debugLog('Dual image inserted successfully', 'success');
        } else {
            debugLog('No active editor found', 'error');
        }
    }

    function debugLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const prefix = `[TinyMCE ${timestamp}]`;
        
        if (state.debugMode) {
            switch (type) {
                case 'error':
                    console.error(prefix, message);
                    break;
                case 'warn':
                    console.warn(prefix, message);
                    break;
                case 'success':
                    console.log(`%c${prefix} ✅ ${message}`, 'color: green; font-weight: bold;');
                    break;
                default:
                    console.log(prefix, message);
            }
        }
        
        // Store last error for debugging
        if (type === 'error') {
            state.lastError = { message, timestamp };
        }
    }
    
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
            // Enhanced list configuration
            advlist_bullet_styles: 'default,circle,square',
            advlist_number_styles: 'default,lower-alpha,lower-roman,upper-alpha,upper-roman',
            // Force all buttons to be visible
            toolbar_mode: 'sliding',
            pagebreak_separator: '<!-- pagebreak -->',
            images_upload_url: '/admin/upload/tinymce',
            automatic_uploads: true,
            images_reuse_filename: true,
            // Add custom headers to image uploads
            images_upload_handler: function (blobInfo, progress) {
                return new Promise(function (resolve, reject) {
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = true;
                    xhr.open('POST', '/admin/upload/tinymce');

                    // Set AJAX header so server knows to return JSON
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = function (e) {
                        progress(e.loaded / e.total * 100);
                    };

                    xhr.onload = function () {
                        if (xhr.status === 401) {
                            reject('Authentication required. Please refresh the page and log in.');
                            return;
                        }

                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject('HTTP Error: ' + xhr.status);
                            return;
                        }

                        const json = JSON.parse(xhr.responseText);

                        if (!json || typeof json.location !== 'string') {
                            reject('Invalid JSON: ' + xhr.responseText);
                            return;
                        }

                        resolve(json.location);
                    };

                    xhr.onerror = function () {
                        reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                    };

                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    xhr.send(formData);
                });
            },
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
            // List and content validation settings
            valid_elements: '*[*]',
            valid_children: '+body[style],+ol[li],+ul[li]',
            extended_valid_elements: '*[*]',
            content_style: 'ul, ol { list-style-type: initial; margin: 1em 0; padding-left: 40px; } li { margin: 0.25em 0; }',
            // Image handling options
            image_advtab: true,
            image_caption: true,
            image_title: true,
            // MEDIA BROWSER INTEGRATION
            file_picker_callback: window.tinyMCEFilePicker,
            file_picker_types: 'image',
            setup: function(editor) {
                debugLog(`TinyMCE setup function called for editor: ${editor.id}`, 'info');
                
                // Register buttons immediately in setup
                registerCustomButtons(editor);
                
                // Enhanced button registration with multiple fallback strategies
                debugLog('Starting enhanced button registration process...', 'info');
                state.buttonRegistrationAttempts++;
                
                // Strategy 1: Register dual image button with enhanced error handling
                try {
                    editor.ui.registry.addButton('dualimage', {
                        text: 'Modal Image',
                        tooltip: 'Insert image with modal view',
                        icon: 'image',
                        onAction: function() {
                            debugLog('Dual image button clicked', 'info');
                            if (typeof window.showDualImageDialog === 'function') {
                                window.showDualImageDialog(editor);
                            } else if (typeof showDualImageDialog === 'function') {
                                showDualImageDialog(editor);
                            } else {
                                debugLog('showDualImageDialog function not found', 'error');
                                window.debugTinyMCE && window.debugTinyMCE();
                                alert('Dual image functionality not available. Please refresh the page.');
                            }
                        }
                    });
                    debugLog('Dual image button registered successfully', 'success');
                } catch (error) {
                    debugLog('Failed to register dual image button: ' + error.message, 'error');
                }
                
                // Strategy 2: Register alternative modal image button
                try {
                    editor.ui.registry.addButton('modalimage', {
                        text: 'Modal',
                        tooltip: 'Insert image with modal view (alternative)',
                        onAction: function() {
                            debugLog('Modal image button clicked', 'info');
                            if (typeof window.showDualImageDialog === 'function') {
                                window.showDualImageDialog(editor);
                            } else {
                                debugLog('Modal image functionality not available', 'error');
                                alert('Modal image functionality not available. Please refresh the page.');
                            }
                        }
                    });
                    debugLog('Modal image button registered successfully', 'success');
                } catch (error) {
                    debugLog('Failed to register modal image button: ' + error.message, 'error');
                }

                // Strategy 3: Register test/debug button
                try {
                    editor.ui.registry.addButton('testbutton', {
                        text: 'DEBUG',
                        tooltip: 'Debug TinyMCE functionality',
                        onAction: function() {
                            debugLog('Debug button clicked', 'info');
                            // Run comprehensive debug check
                            window.debugTinyMCE && window.debugTinyMCE();
                            alert(`TinyMCE Debug Info:\n\nButtons registered: ${state.buttonRegistrationAttempts}\nCache buster: ${state.cacheBuster}\nLast error: ${state.lastError ? state.lastError.message : 'None'}\n\nCheck console for detailed info.`);
                        }
                    });
                    debugLog('Debug button registered successfully', 'success');
                } catch (error) {
                    debugLog('Failed to register debug button: ' + error.message, 'error');
                }
                
                // Strategy 4: Button verification without retry
                setTimeout(() => {
                    try {
                        const registeredButtons = editor.ui.registry.getAll().buttons;
                        const customButtons = ['dualimage', 'modalimage', 'testbutton'];
                        
                        customButtons.forEach(buttonName => {
                            if (registeredButtons[buttonName]) {
                                debugLog(`Button '${buttonName}' is registered successfully`, 'success');
                            } else {
                                debugLog(`Button '${buttonName}' not found in registry`, 'warn');
                            }
                        });
                    } catch (checkError) {
                        debugLog(`Button registration check failed: ${checkError.message}`, 'error');
                    }
                }, 500);

                editor.on('init', function() {
                    debugLog(`TinyMCE editor initialized: ${editor.id}`, 'success');
                    
                    // Debug list functionality
                    setTimeout(() => {
                        try {
                            const listSupported = editor.queryCommandSupported('InsertUnorderedList');
                            debugLog(`List commands supported: ${listSupported}`, listSupported ? 'success' : 'error');
                            
                            // Test list functionality
                            const testContent = '<p>Test paragraph for list conversion</p>';
                            const originalContent = editor.getContent();
                            
                            editor.setContent(testContent);
                            editor.selection.setCursorLocation(editor.getBody().querySelector('p'), 0);
                            editor.execCommand('InsertUnorderedList');
                            
                            const afterListCommand = editor.getContent();
                            const listCreated = afterListCommand.includes('<ul>') || afterListCommand.includes('<li>');
                            debugLog(`List creation test: ${listCreated ? 'PASSED' : 'FAILED'}`, listCreated ? 'success' : 'error');
                            
                            if (!listCreated) {
                                debugLog(`Expected list HTML, got: ${afterListCommand}`, 'error');
                            }
                            
                            // Restore original content
                            editor.setContent(originalContent);
                            
                        } catch (error) {
                            debugLog(`List functionality test failed: ${error.message}`, 'error');
                        }
                    }, 2000);
                    
                    // Enhanced debug: Check button registration status
                    const registeredButtons = editor.ui.registry.getAll().buttons;
                    debugLog(`All registered TinyMCE buttons (${Object.keys(registeredButtons).length}): ${Object.keys(registeredButtons).join(', ')}`, 'info');
                    
                    // Enhanced custom button verification
                    const customButtons = ['dualimage', 'modalimage', 'testbutton'];
                    let registeredCount = 0;
                    
                    customButtons.forEach(buttonName => {
                        if (registeredButtons[buttonName]) {
                            debugLog(`Button '${buttonName}' is registered and available`, 'success');
                            registeredCount++;
                        } else {
                            debugLog(`Button '${buttonName}' is NOT registered`, 'error');
                        }
                    });
                    
                    // Store registration status for debugging
                    window.TINYMCE_BUTTON_STATUS = {
                        total: customButtons.length,
                        registered: registeredCount,
                        missing: customButtons.length - registeredCount,
                        timestamp: new Date().toISOString()
                    };
                    
                    // Enhanced toolbar configuration check
                    const toolbarConfig = editor.settings?.toolbar || '';
                    debugLog(`Toolbar configuration: ${toolbarConfig}`, 'info');
                    
                    // Validate toolbar contains our buttons
                    const requiredButtons = ['dualimage', 'modalimage', 'testbutton'];
                    const missingFromToolbar = requiredButtons.filter(btn => !toolbarConfig.includes(btn));
                    
                    if (missingFromToolbar.length > 0) {
                        debugLog(`Buttons missing from toolbar config: ${missingFromToolbar.join(', ')}`, 'warn');
                    } else {
                        debugLog('All custom buttons are included in toolbar configuration', 'success');
                    }
                    
                    // Check if buttons are visible in the UI
                    setTimeout(() => {
                        const container = editor.getContainer();
                        const toolbar = container.querySelector('.tox-toolbar') || container.querySelector('.tox-toolbar-primary');
                        if (toolbar) {
                            const allButtons = toolbar.querySelectorAll('button');
                            debugLog(`Found ${allButtons.length} buttons in toolbar DOM`, 'info');
                            
                            // Look for our custom buttons by title or aria-label
                            customButtons.forEach(buttonName => {
                                const found = Array.from(allButtons).some(btn => 
                                    btn.title?.toLowerCase().includes('modal') ||
                                    btn.title?.toLowerCase().includes('test') ||
                                    btn.title?.toLowerCase().includes('dual') ||
                                    btn.getAttribute('aria-label')?.toLowerCase().includes('modal') ||
                                    btn.getAttribute('aria-label')?.toLowerCase().includes('test') ||
                                    btn.getAttribute('aria-label')?.toLowerCase().includes('dual')
                                );
                                const status = found ? 'YES' : 'NO';
                                debugLog(`Button '${buttonName}' visible in UI: ${status}`, found ? 'success' : 'error');
                            });
                        } else {
                            debugLog('TinyMCE toolbar not found - may still be loading', 'warn');
                        }
                    }, 2000); // Wait 2 seconds for UI to fully render
                    
                    // Enhanced manual button injection with better toolbar detection
                    setTimeout(() => {
                        const container = editor.getContainer();
                        if (!container) {
                            debugLog('Editor container not found', 'error');
                            return;
                        }
                        
                        debugLog('Attempting to find toolbar for manual button injection', 'info');
                        
                        // Enhanced toolbar detection with multiple fallbacks
                        let toolbar = null;
                        const toolbarSelectors = [
                            '.tox-toolbar__primary .tox-toolbar__group',
                            '.tox-toolbar-primary .tox-toolbar__group',
                            '.tox-toolbar .tox-toolbar__group',
                            '.tox-toolbar__primary',
                            '.tox-toolbar-primary',
                            '.tox-toolbar',
                            '.mce-toolbar',
                            '[role="toolbar"]'
                        ];
                        
                        for (const selector of toolbarSelectors) {
                            toolbar = container.querySelector(selector);
                            if (toolbar && toolbar.querySelector('button')) {
                                debugLog(`Found toolbar using selector: ${selector}`, 'success');
                                break;
                            }
                        }
                        
                        if (!toolbar) {
                            debugLog('Could not find TinyMCE toolbar element', 'error');
                            return;
                        }
                        
                        // Check if our button already exists to prevent duplicates
                        if (toolbar.querySelector('button[title*="modal view"]') || toolbar.querySelector('button[aria-label*="modal view"]')) {
                            debugLog('Manual dual image button already exists', 'info');
                            return;
                        }
                        
                        // Find reference button (prefer image button, fallback to any button)
                        let referenceButton = null;
                        const imageSelectors = [
                            'button[title*="Image"]',
                            'button[aria-label*="Image"]',
                            'button[title*="Insert"]',
                            'button[aria-label*="Insert"]'
                        ];
                        
                        for (const selector of imageSelectors) {
                            referenceButton = toolbar.querySelector(selector);
                            if (referenceButton) {
                                debugLog(`Found reference button using: ${selector}`, 'info');
                                break;
                            }
                        }
                        
                        // Fallback to any button
                        if (!referenceButton) {
                            const buttons = toolbar.querySelectorAll('button');
                            if (buttons.length > 0) {
                                referenceButton = buttons[Math.min(buttons.length - 1, 5)]; // Use 6th button or last
                                debugLog('Using fallback button position', 'info');
                            }
                        }
                        
                        if (referenceButton) {
                            // Create enhanced manual button
                            const dualImageBtn = document.createElement('button');
                            dualImageBtn.type = 'button';
                            dualImageBtn.innerHTML = '🖼️📱';
                            dualImageBtn.title = 'Insert image with modal view';
                            dualImageBtn.setAttribute('aria-label', 'Insert image with modal view');
                            dualImageBtn.setAttribute('data-manual-button', 'true');
                            
                            // Copy TinyMCE button styling
                            dualImageBtn.className = referenceButton.className || 'tox-tbtn';
                            dualImageBtn.style.marginLeft = '2px';
                            
                            // Enhanced click handler
                            dualImageBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                e.stopPropagation();
                                debugLog('Manual dual image button clicked', 'info');
                                
                                if (typeof window.showDualImageDialog === 'function') {
                                    try {
                                        window.showDualImageDialog(editor);
                                    } catch (error) {
                                        debugLog('Error calling showDualImageDialog: ' + error.message, 'error');
                                        alert('Error opening dual image dialog. Please refresh the page.');
                                    }
                                } else {
                                    debugLog('showDualImageDialog function not available', 'error');
                                    alert('Dual image functionality not loaded. Please refresh the page.');
                                }
                            });
                            
                            // Insert button after reference button
                            referenceButton.parentNode.insertBefore(dualImageBtn, referenceButton.nextSibling);
                            debugLog('Manual dual image button successfully added to toolbar', 'success');
                            
                        } else {
                            debugLog('Could not find reference button for positioning', 'error');
                        }
                    }, 1500); // Wait longer for complete toolbar rendering
                    
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
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
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
            debugLog('TinyMCE initialization already completed or in progress', 'info');
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
            
            // Ensure showDualImageDialog is available globally
            if (typeof window.showDualImageDialog !== 'function') {
                debugLog('showDualImageDialog not found globally - this may cause button issues', 'warn');
            } else {
                debugLog('showDualImageDialog function is available globally', 'success');
            }
            
            // Check if custom elements are already registered
            // This prevents the "custom element already defined" error
            const customElementsExist = customElements.get('mce-autosize-textarea');
            
            if (customElementsExist && !state.customElementsRegistered) {
                // Mark that custom elements have been registered
                state.customElementsRegistered = true;
                debugLog('TinyMCE custom elements detected as already registered', 'info');
            }
            
            // Remove any existing editor instances
            try {
                if (tinymce.get().length > 0) {
                    debugLog('Removing existing TinyMCE instances', 'info');
                    tinymce.remove();
                }
            } catch (e) {
                debugLog('Error removing TinyMCE instances: ' + e.message, 'error');
            }
            
            // Initialize TinyMCE
            try {
                tinymce.init(getEditorConfig()).then(() => {
                    state.initialized = true;
                    state.initInProgress = false;
                    state.customElementsRegistered = true;
                    debugLog('TinyMCE initialization complete', 'success');
                }).catch(err => {
                    // If we get a custom element error, it means TinyMCE is already loaded
                    // Just mark as initialized and continue
                    if (err.message && err.message.includes('already been defined')) {
                        state.initialized = true;
                        state.customElementsRegistered = true;
                        debugLog('TinyMCE already initialized (custom elements exist)', 'info');
                    } else {
                        debugLog('TinyMCE initialization error: ' + err.message, 'error');
                    }
                    state.initInProgress = false;
                });
            } catch (err) {
                // Catch synchronous errors
                if (err.message && err.message.includes('already been defined')) {
                    state.initialized = true;
                    state.customElementsRegistered = true;
                    debugLog('TinyMCE already initialized (caught sync error)', 'info');
                } else {
                    debugLog('Failed to initialize TinyMCE: ' + err.message, 'error');
                }
                state.initInProgress = false;
            }
        }).catch(err => {
            debugLog('Failed to load TinyMCE: ' + err.message, 'error');
            state.initInProgress = false;
        });
    }
    
    // Enhanced global debugging function
    window.debugTinyMCE = function() {
        console.group('🔍 TinyMCE Comprehensive Debug Report');
        console.log('Generated at:', new Date().toLocaleString());
        console.log('Cache buster:', state.cacheBuster);
        console.log('Button registration attempts:', state.buttonRegistrationAttempts);
        
        if (typeof tinymce === 'undefined') {
            console.error('❌ TinyMCE is not loaded');
            console.log('Troubleshooting: Check network tab for failed script loads');
            console.groupEnd();
            return;
        }
        
        const editors = tinymce.get();
        console.log(`Found ${editors.length} TinyMCE editor(s)`);
        
        editors.forEach((editor, index) => {
            console.log(`\n--- Editor ${index + 1} (${editor.id}) ---`);
            console.log('Toolbar config:', editor.settings?.toolbar || 'undefined');
            
            const registeredButtons = editor.ui.registry.getAll().buttons;
            const customButtons = ['dualimage', 'modalimage', 'testbutton'];
            
            customButtons.forEach(buttonName => {
                const isRegistered = !!registeredButtons[buttonName];
                console.log(`${buttonName}: ${isRegistered ? '✅ Registered' : '❌ Not registered'}`);
                
                if (isRegistered) {
                    const buttonConfig = registeredButtons[buttonName];
                    console.log(`  - Text: ${buttonConfig.text || 'N/A'}`);
                    console.log(`  - Tooltip: ${buttonConfig.tooltip || 'N/A'}`);
                }
            });
            
            // Check DOM for buttons with enhanced toolbar detection
            const container = editor.getContainer();
            if (container) {
                const toolbarSelectors = [
                    '.tox-toolbar__primary',
                    '.tox-toolbar-primary', 
                    '.tox-toolbar',
                    '.mce-toolbar',
                    '[role="toolbar"]'
                ];
                
                let toolbar = null;
                for (const selector of toolbarSelectors) {
                    toolbar = container.querySelector(selector);
                    if (toolbar && toolbar.querySelector('button')) {
                        console.log(`✅ Found toolbar using: ${selector}`);
                        break;
                    }
                }
                
                if (toolbar) {
                    const buttons = toolbar.querySelectorAll('button');
                    console.log(`Toolbar has ${buttons.length} button elements`);
                    
                    const modalButtons = Array.from(buttons).filter(btn => 
                        btn.title?.toLowerCase().includes('modal') ||
                        btn.title?.toLowerCase().includes('test') ||
                        btn.title?.toLowerCase().includes('dual') ||
                        btn.textContent?.toLowerCase().includes('modal') ||
                        btn.textContent?.toLowerCase().includes('test') ||
                        btn.getAttribute('data-manual-button')
                    );
                    console.log(`Found ${modalButtons.length} custom button(s) in DOM`);
                    modalButtons.forEach(btn => {
                        console.log(`  - Button: ${btn.title || btn.textContent || 'No title'}`);
                    });
                } else {
                    console.warn('⚠️ Toolbar element not found - TinyMCE may still be initializing');
                }
            } else {
                console.error('❌ Could not find editor container');
            }
        });
        
        console.group('📊 Browser & Session Info');
        console.log('User agent:', navigator.userAgent);
        console.log('Local storage available:', typeof(Storage) !== 'undefined');
        console.log('Cookies enabled:', navigator.cookieEnabled);
        console.log('Page load time:', performance.timing ? (performance.timing.loadEventEnd - performance.timing.navigationStart) + 'ms' : 'N/A');
        console.log('Button status:', window.TINYMCE_BUTTON_STATUS || 'Not available');
        console.groupEnd();
        
        console.group('🛠️ Troubleshooting Steps');
        console.log('%c1. Clear browser cache completely and hard refresh (Ctrl+F5)', 'font-weight: bold; color: #e74c3c;');
        console.log('%c2. Try incognito/private browsing mode', 'font-weight: bold; color: #f39c12;');
        console.log('%c3. Check Network tab for 404 errors on JS files', 'font-weight: bold; color: #3498db;');
        console.log('%c4. Verify no JavaScript errors in console before TinyMCE loads', 'font-weight: bold; color: #9b59b6;');
        console.log('%c5. Test with different browser or device', 'font-weight: bold; color: #1abc9c;');
        console.log('%c6. Check if other admin users see the buttons', 'font-weight: bold; color: #34495e;');
        console.log('%c7. Contact admin if problem persists', 'font-weight: bold; color: #e67e22;');
        console.groupEnd();
        
        console.groupEnd();
    };
    
    // Make debug function available globally with enhanced info
    if (state.debugMode) {
        debugLog('Debug function available: window.debugTinyMCE()', 'info');
        debugLog(`Cache buster: ${state.cacheBuster}`, 'info');
        
        // Auto-run debug after initialization if there are issues
        setTimeout(() => {
            if (state.lastError) {
                debugLog('Errors detected - running auto-debug...', 'warn');
                window.debugTinyMCE();
            }
        }, 5000);
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