/**
 * Media Browser Integration for TinyMCE
 * Provides Drupal-style media management while preserving dual image functionality
 */

(function() {
    'use strict';

    // Store reference to TinyMCE callback
    window.MediaBrowser = {
        currentCallback: null,
        currentEditor: null,

        /**
         * Open media browser modal
         */
        open: function(callback, value, meta) {
            this.currentCallback = callback;
            this.currentEditor = tinymce.activeEditor;

            // Create iframe modal
            const modal = document.createElement('div');
            modal.id = 'mediaBrowserModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            const iframe = document.createElement('iframe');
            iframe.src = '/admin/media/browser';
            iframe.style.cssText = `
                width: 90%;
                height: 90%;
                max-width: 1400px;
                max-height: 900px;
                border: none;
                border-radius: 8px;
                background: white;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            `;

            modal.appendChild(iframe);
            document.body.appendChild(modal);

            // Listen for messages from iframe
            window.addEventListener('message', this.handleMessage.bind(this));

            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close();
                }
            });
        },

        /**
         * Handle messages from media browser
         */
        handleMessage: function(event) {
            // Security: only accept messages from same origin
            if (event.origin !== window.location.origin) {
                return;
            }

            const data = event.data;

            if (data.action === 'close') {
                this.close();
            } else if (data.action === 'insertImage') {
                this.insertImage(data.image);
            }
        },

        /**
         * Insert selected image into TinyMCE
         */
        insertImage: function(imageData) {
            if (!this.currentCallback) {
                console.error('No callback available');
                return;
            }

            if (imageData.type === 'dual') {
                // Insert dual image with modal functionality
                const html = `
                    <img
                        src="${imageData.displaySrc}"
                        alt="${imageData.alt || ''}"
                        ${imageData.title ? `title="${imageData.title}"` : ''}
                        data-modal-src="${imageData.modalSrc}"
                        onclick="openImageModal('${imageData.modalSrc}', '${imageData.alt || ''}')"
                        style="cursor: pointer; max-width: 100%;"
                        class="modal-image"
                    />
                `;

                this.currentEditor.insertContent(html);
            } else {
                // Insert regular image
                this.currentCallback(imageData.src, {
                    alt: imageData.alt || '',
                    title: imageData.title || '',
                    width: imageData.width || null,
                    height: imageData.height || null
                });
            }

            this.close();
        },

        /**
         * Close media browser
         */
        close: function() {
            const modal = document.getElementById('mediaBrowserModal');
            if (modal) {
                modal.remove();
            }
            this.currentCallback = null;
            this.currentEditor = null;
        }
    };

    /**
     * TinyMCE file_picker_callback wrapper
     * This gets called when user clicks "Insert/Edit Image" in TinyMCE
     */
    window.tinyMCEFilePicker = function(callback, value, meta) {
        // Only handle image types
        if (meta.filetype === 'image') {
            window.MediaBrowser.open(callback, value, meta);
        }
    };

    console.log('✓ Media Browser Integration loaded');
})();
