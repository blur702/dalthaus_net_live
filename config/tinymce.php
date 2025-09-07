<?php
/**
 * TinyMCE Configuration
 * 
 * GPL Licensed Version Configuration
 * Using TinyMCE Community Edition (GPL v2)
 * 
 * @package CMS
 * @version 1.0.0
 */

return [
    /**
     * TinyMCE License Type
     * Using GPL/LGPL version from jsDelivr CDN
     */
    'license' => 'gpl',
    
    /**
     * CDN URL for GPL version
     * Using jsDelivr which hosts the open-source version
     */
    'cdn_url' => 'https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js',
    
    /**
     * Default Configuration for Editor
     */
    'default_config' => [
        'height' => 500,
        'plugins' => [
            'advlist',
            'autolink',
            'lists',
            'link',
            'image',
            'charmap',
            'preview',
            'anchor',
            'searchreplace',
            'visualblocks',
            'code',
            'fullscreen',
            'insertdatetime',
            'media',
            'table',
            'help',
            'wordcount',
            'pagebreak'
        ],
        'toolbar' => 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | pagebreak | image link media | code fullscreen | help',
        'content_style' => 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
        'images_upload_url' => '/admin/content/upload-image',
        'images_upload_credentials' => true,
        'file_picker_types' => 'image',
        'automatic_uploads' => true,
        'license_key' => 'gpl', // Explicitly marking as GPL version
        'branding' => false, // GPL version allows removing branding
        'promotion' => false // No promotion in GPL version
    ]
];