/**
 * TinyMCE Initialization Script
 * GPL Version Configuration
 */

// Default TinyMCE configuration for the CMS
const tinymceConfig = {
    license_key: 'gpl', // Using GPL version
    height: 500,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'pagebreak'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | pagebreak | image link media | code fullscreen | help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    images_upload_url: '/admin/content/upload-image',
    images_upload_credentials: true,
    file_picker_types: 'image',
    automatic_uploads: true,
    branding: false, // Remove "Powered by TinyMCE" - allowed in GPL version
    promotion: false, // No upgrade promotion
    
    // Pagebreak plugin configuration
    pagebreak_separator: '<!--pagebreak-->',
    
    // Additional settings for better UX
    menubar: true,
    statusbar: true,
    resize: true,
    paste_as_text: false,
    paste_auto_cleanup_on_paste: true,
    
    // Setup callback for autosave
    setup: function (editor) {
        editor.on('change', function () {
            if (typeof triggerAutosave === 'function') {
                triggerAutosave();
            }
        });
        
        // Add custom pagebreak button behavior
        editor.ui.registry.addButton('customPagebreak', {
            text: 'Page Break',
            onAction: function () {
                editor.insertContent('<!--pagebreak-->');
            }
        });
    }
};

// Initialize TinyMCE when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all textareas with class 'tinymce-editor'
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: 'textarea.tinymce-editor',
            ...tinymceConfig
        });
        
        // Also initialize specific ID if needed
        if (document.getElementById('body')) {
            tinymce.init({
                selector: '#body',
                ...tinymceConfig
            });
        }
    }
});