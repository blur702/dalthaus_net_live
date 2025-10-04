// Minimal TinyMCE configuration with custom buttons
console.log('Loading minimal TinyMCE configuration...');

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready, initializing TinyMCE...');
    
    // Dual image dialog function - connects to the admin layout modal system
    function openDualImageDialog() {
        console.log('Opening dual image dialog...');
        
        const editor = tinymce.activeEditor;
        if (!editor) {
            console.error('No active TinyMCE editor found');
            return;
        }
        
        // Use the showDualImageDialog function from admin layout
        if (typeof showDualImageDialog === 'function') {
            showDualImageDialog(editor);
        } else {
            console.error('showDualImageDialog function not found - admin layout may not be loaded');
            // Fallback to simple prompts if admin modal system isn't available
            const displayImage = prompt('Enter display image URL:');
            if (!displayImage) return;
            
            const modalImage = prompt('Enter modal image URL (optional):') || displayImage;
            const html = `<img src="${displayImage}" data-modal-src="${modalImage}" alt="Dual Image" style="cursor: pointer; max-width: 100%;" onclick="openImageModal('${modalImage}', 'Dual Image')">`;
            editor.insertContent(html);
            console.log('Dual image inserted successfully (fallback mode)');
        }
    }
    
    // Initialize TinyMCE when it's loaded
    function initTinyMCE() {
        if (typeof tinymce === 'undefined') {
            console.log('TinyMCE not loaded yet, retrying in 500ms...');
            setTimeout(initTinyMCE, 500);
            return;
        }
        
        console.log('TinyMCE available, initializing...');
        
        tinymce.init({
            selector: 'textarea#body, textarea.tinymce-editor, textarea[data-tinymce="true"]',
            height: 500,
            menubar: false,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount pagebreak',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image dualimage | pagebreak code',
            toolbar_mode: 'sliding',
            pagebreak_separator: '<!-- pagebreak -->',
            images_upload_url: '/admin/upload/tinymce',
            automatic_uploads: true,
            images_reuse_filename: true,
            browser_spellcheck: true,
            contextmenu: false,
            promotion: false,
            branding: false,
            relative_urls: false,
            remove_script_host: false,
            document_base_url: window.location.origin + '/',
            verify_html: false,
            image_advtab: true,
            image_caption: true,
            image_title: true,
            setup: function(editor) {
                console.log('TinyMCE setup function called for editor:', editor.id);
                
                // Register dual image button
                editor.ui.registry.addButton('dualimage', {
                    text: '🖼️📱',
                    tooltip: 'Insert Dual Image (Display + Modal)',
                    onAction: function() {
                        openDualImageDialog();
                    }
                });
                
                console.log('Custom buttons registered successfully');
                
                // Log when editor is ready
                editor.on('init', function() {
                    console.log('TinyMCE editor initialized successfully');
                });
            }
        }).then(function(editors) {
            console.log('TinyMCE initialization complete, editors:', editors.length);
        }).catch(function(err) {
            console.error('TinyMCE initialization failed:', err);
        });
    }
    
    // Start initialization
    initTinyMCE();
});