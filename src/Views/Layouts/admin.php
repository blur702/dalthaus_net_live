<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($page_title ?? 'Admin') ?> - <?= $this->escape($settings['site_title'] ?? 'CMS') ?></title>
    
    <!-- Aggressive no-cache meta tags for admin pages -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📝</text></svg>">
    
    <!-- Enhanced cache-busting and performance meta tags -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="cache-buster" content="<?= time() ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Prevent custom element conflicts BEFORE loading any scripts -->
    <script>
        // Comprehensive fix for custom element redefinition errors
        (function() {
            // Store the original customElements.define method
            const originalDefine = window.customElements?.define;
            if (!originalDefine) return;
            
            // Keep track of defined elements
            const definedElements = new Set();
            
            // Override customElements.define to prevent redefinition
            window.customElements.define = function(name, constructor, options) {
                // Check if element is already defined
                if (window.customElements.get(name) || definedElements.has(name)) {
                    console.warn(`Custom element '${name}' already defined, skipping redefinition`);
                    return;
                }
                
                try {
                    // Call original define method
                    originalDefine.call(window.customElements, name, constructor, options);
                    definedElements.add(name);
                } catch (e) {
                    console.warn(`Failed to define custom element '${name}':`, e.message);
                }
            };
            
            // Also protect against direct access attempts
            const originalGet = window.customElements?.get;
            if (originalGet) {
                window.customElements.get = function(name) {
                    try {
                        return originalGet.call(window.customElements, name);
                    } catch (e) {
                        return undefined;
                    }
                };
            }
        })();
    </script>
    
    <!-- TinyMCE with enhanced cache-busting -->
    <?php
    $tinymce_version = time(); // Force reload every time for admin
    $js_file_path = __DIR__ . '/../../../assets/js/tinymce-single.js';
    $js_version = file_exists($js_file_path) ? filemtime($js_file_path) : time();
    ?>
    <script>
        // Clear any TinyMCE cache on page load
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => {
                    if (name.includes('tinymce') || name.includes('cdn.jsdelivr.net')) {
                        caches.delete(name);
                    }
                });
            });
        }
        
        // Prevent aggressive caching
        window.CACHE_BUSTER = '<?= $tinymce_version ?>';
        window.ADMIN_DEBUG = true;
        console.log('Admin cache buster:', window.CACHE_BUSTER);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js?v=<?= $tinymce_version ?>" referrerpolicy="origin"></script>
    <script src="/assets/js/tinymce-minimal.js?v=<?= $js_version ?>&cb=<?= $tinymce_version ?>" defer></script>
    
    <style>
        body { background-color: #f8f9fa; }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu { display: none; }
        .tox-tinymce { border-radius: 0.375rem; border: 1px solid #D1D5DB; }
        
        /* 4:3 aspect ratio for admin images */
        .admin-image-43 {
            aspect-ratio: 4 / 3;
            object-fit: cover;
            width: 100%;
            height: auto;
            max-width: 64px;
            max-height: 48px;
        }

        /* Dual Image Dialog Styles */
        .dual-image-dialog {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
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
        }

        .dual-image-content {
            position: relative;
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .dual-image-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dual-image-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            color: #374151;
        }

        .dual-image-body {
            padding: 20px;
        }

        .upload-section {
            margin-bottom: 20px;
        }

        .upload-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        .upload-section input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
            margin-top: 5px;
        }

        .form-fields {
            margin-bottom: 20px;
        }

        .form-fields label {
            display: block;
            margin-bottom: 12px;
            font-weight: 500;
            color: #374151;
        }

        .form-fields input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-top: 4px;
        }

        .dialog-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-cancel, .btn-insert {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid;
        }

        .btn-cancel {
            background: white;
            color: #6b7280;
            border-color: #d1d5db;
        }

        .btn-cancel:hover {
            background: #f9fafb;
        }

        .btn-insert {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-insert:hover {
            background: #2563eb;
        }

        .btn-insert:disabled {
            background: #9ca3af;
            border-color: #9ca3af;
            cursor: not-allowed;
        }

        /* Image Modal Styles */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }

        .modal-close:hover {
            opacity: 0.7;
        }

        /* Clickable images */
        .clickable-image {
            transition: opacity 0.2s ease;
        }

        .clickable-image:hover {
            opacity: 0.9;
        }
    </style>

    <!-- Sortable.js for drag and drop reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <nav class="flex items-center space-x-8">
                    <a href="/admin/dashboard" class="text-gray-900 font-bold text-lg">CMS</a>
                    <a href="/admin/articles" class="text-gray-700 hover:text-gray-900">Articles</a>
                    <a href="/admin/photobooks" class="text-gray-700 hover:text-gray-900">Photobooks</a>
                    <a href="/admin/pages" class="text-gray-700 hover:text-gray-900">Pages</a>
                    <a href="/admin/menus" class="text-gray-700 hover:text-gray-900">Menus</a>
                    <a href="/admin/users" class="text-gray-700 hover:text-gray-900">Users</a>
                    <a href="/admin/settings" class="text-gray-700 hover:text-gray-900">Settings</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <div class="dropdown relative">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <span class="text-sm font-medium"><?= $this->escape($current_user['username'] ?? 'Admin') ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="dropdown-menu absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50 min-w-[160px]">
                            <a href="/" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Site</a>
                            <hr class="my-1">
                            <form action="/admin/logout" method="POST" class="block">
                                <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="flash-message p-4 rounded-md <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
            <?= $this->escape($flash['message']) ?>
        </div>
    </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $content ?? '' ?>
    </main>

    <script>
        // Note: TinyMCE initialization is now handled by tinymce-single.js
        // This prevents conflicts between multiple initialization scripts

        // Dual Image Dialog Function - exposed globally for TinyMCE integration
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
                            <div class="upload-section">
                                <label>
                                    <strong>Display Image</strong> (shown on page)
                                    <input type="file" name="display_image" accept="image/*" required>
                                </label>
                            </div>
                            <div class="upload-section">
                                <label>
                                    <strong>Modal Image</strong> (shown when clicked - optional)
                                    <input type="file" name="modal_image" accept="image/*">
                                </label>
                            </div>
                            <div class="form-fields">
                                <label>
                                    Alt Text
                                    <input type="text" id="altText" placeholder="Describe the image">
                                </label>
                                <label>
                                    Width (optional)
                                    <input type="number" id="imageWidth" placeholder="e.g., 300">
                                </label>
                            </div>
                            <div class="dialog-actions">
                                <button type="button" onclick="closeDualImageDialog()" class="btn-cancel">Cancel</button>
                                <button type="submit" class="btn-insert">Insert Image</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);

            // Handle form submission
            document.getElementById('dualImageForm').addEventListener('submit', function(e) {
                e.preventDefault();
                uploadDualImage(editor, this);
            });
        }

        window.closeDualImageDialog = function() {
            const dialog = document.querySelector('.dual-image-dialog');
            if (dialog) {
                dialog.remove();
            }
        }

        function uploadDualImage(editor, form) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.btn-insert');
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
        }

        // Auto-dismiss flash messages
        setTimeout(function() {
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                flashMessage.style.transition = 'opacity 0.5s';
                flashMessage.style.opacity = '0';
                setTimeout(() => flashMessage.remove(), 500);
            }
        }, 5000);

        // Enhanced debugging capabilities for TinyMCE issues
        window.showTinyMCEDebugPanel = function() {
            // Remove existing debug panel if present
            const existingPanel = document.getElementById('tinymce-debug-panel');
            if (existingPanel) {
                existingPanel.remove();
                return;
            }

            const debugPanel = document.createElement('div');
            debugPanel.id = 'tinymce-debug-panel';
            debugPanel.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                width: 400px;
                max-height: 80vh;
                background: white;
                border: 2px solid #3b82f6;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
                z-index: 10001;
                font-family: monospace;
                font-size: 12px;
                overflow-y: auto;
            `;

            const buttonStatus = window.TINYMCE_BUTTON_STATUS || { total: 0, registered: 0, missing: 0 };
            const tinymceState = window.TINYMCE_STATE || {};
            
            let statusColor = buttonStatus.missing === 0 ? '#10b981' : '#ef4444';
            let statusText = buttonStatus.missing === 0 ? 'ALL WORKING' : `${buttonStatus.missing} MISSING`;

            debugPanel.innerHTML = `
                <div style="padding: 15px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
                    <h3 style="margin: 0; color: #1f2937; display: flex; justify-content: space-between; align-items: center;">
                        🔍 TinyMCE Debug Panel
                        <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">×</button>
                    </h3>
                </div>
                <div style="padding: 15px;">
                    <div style="margin-bottom: 15px;">
                        <strong style="color: ${statusColor};">Status: ${statusText}</strong><br>
                        <small>Buttons: ${buttonStatus.registered}/${buttonStatus.total} registered</small>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Cache Info:</strong><br>
                        Cache Buster: ${window.CACHE_BUSTER || 'Not set'}<br>
                        Debug Mode: ${tinymceState.debugMode ? 'ON' : 'OFF'}<br>
                        Registration Attempts: ${tinymceState.buttonRegistrationAttempts || 0}
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Quick Actions:</strong><br>
                        <button onclick="window.debugTinyMCE && window.debugTinyMCE()" 
                                style="background: #3b82f6; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin: 2px; cursor: pointer;">
                            Run Full Debug
                        </button>
                        <button onclick="location.reload()" 
                                style="background: #10b981; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin: 2px; cursor: pointer;">
                            Hard Refresh
                        </button>
                        <button onclick="window.localStorage.clear(); location.reload()" 
                                style="background: #f59e0b; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin: 2px; cursor: pointer;">
                            Clear Cache & Reload
                        </button>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong>Browser Info:</strong><br>
                        <small>
                            ${navigator.userAgent.split(' ').slice(0, 3).join(' ')}<br>
                            Cookies: ${navigator.cookieEnabled ? 'Enabled' : 'Disabled'}<br>
                            LocalStorage: ${typeof(Storage) !== 'undefined' ? 'Available' : 'Not available'}
                        </small>
                    </div>
                    
                    <div style="background: #f3f4f6; padding: 10px; border-radius: 4px; font-size: 11px;">
                        <strong>Troubleshooting Tips:</strong><br>
                        • If buttons are missing, try "Clear Cache & Reload"<br>
                        • Check console for error messages<br>
                        • Try incognito/private browsing mode<br>
                        • Contact admin if issue persists
                    </div>
                </div>
            `;

            document.body.appendChild(debugPanel);

            // Auto-update button status every 2 seconds
            const updateInterval = setInterval(() => {
                const currentStatus = window.TINYMCE_BUTTON_STATUS;
                if (currentStatus && document.getElementById('tinymce-debug-panel')) {
                    // Update would go here if panel still exists
                } else {
                    clearInterval(updateInterval);
                }
            }, 2000);
        };

        // Add debug panel trigger - show when TinyMCE has issues
        setTimeout(() => {
            if (window.TINYMCE_STATE && window.TINYMCE_STATE.lastError && window.ADMIN_DEBUG) {
                console.warn('TinyMCE issues detected - debug panel available via Ctrl+Shift+D');
            }
        }, 3000);

        // Keyboard shortcut for debug panel (Ctrl+Shift+D)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                window.showTinyMCEDebugPanel();
            }
        });

        // Add floating debug button for easy access (only in debug mode)
        if (window.ADMIN_DEBUG) {
            setTimeout(() => {
                const debugButton = document.createElement('button');
                debugButton.innerHTML = '🔍';
                debugButton.title = 'TinyMCE Debug Panel (Ctrl+Shift+D)';
                debugButton.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: #3b82f6;
                    color: white;
                    border: none;
                    font-size: 20px;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    z-index: 9998;
                    transition: all 0.2s ease;
                `;
                
                debugButton.addEventListener('mouseenter', () => {
                    debugButton.style.transform = 'scale(1.1)';
                });
                
                debugButton.addEventListener('mouseleave', () => {
                    debugButton.style.transform = 'scale(1)';
                });
                
                debugButton.addEventListener('click', window.showTinyMCEDebugPanel);
                
                document.body.appendChild(debugButton);
            }, 1000);
        }

        // Global image modal functions for frontend
        window.openImageModal = function(src, alt) {
            const modal = document.createElement('div');
            modal.className = 'image-modal';
            
            // Create close button
            const closeButton = document.createElement('span');
            closeButton.className = 'modal-close';
            closeButton.innerHTML = '&times;';
            
            // Create image
            const image = document.createElement('img');
            image.src = src;
            image.alt = alt || '';
            
            // Add elements to modal
            modal.appendChild(closeButton);
            modal.appendChild(image);

            // Add event listeners with proper scope
            closeButton.addEventListener('click', function(e) {
                e.stopPropagation();
                closeImageModal();
            });

            image.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Close modal when clicking on overlay
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeImageModal();
                }
            });

            // Close modal with Escape key
            const handleEscape = function(e) {
                if (e.key === 'Escape') {
                    closeImageModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);

            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        };

        window.closeImageModal = function() {
            const modal = document.querySelector('.image-modal');
            if (modal) {
                modal.remove();
                document.body.style.overflow = ''; // Restore scrolling
            }
        };
    </script>
</body>
</html>