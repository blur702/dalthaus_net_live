<?php

use CMS\Utils\Router;

/**
 * Defines the application's routes using route groups for better organization.
 * @param Router $router The router instance.
 */
return function(Router $router) {
    // Public-facing routes
    $router->group(['namespace' => 'Public'], function(Router $router) {
        $router->get('/', 'Home', 'index');
        $router->get('/articles', 'Articles', 'index');
        $router->get('/photobooks', 'Photobooks', 'index');
        $router->get('/article/{alias}', 'Articles', 'show');
        $router->get('/photobook/{alias}', 'Photobooks', 'show');
        $router->get('/page/{alias}', 'Pages', 'show');
    });

    // Admin routes
    $router->group(['prefix' => '/admin', 'namespace' => 'Admin'], function(Router $router) {
        // Handle /admin route - redirect to dashboard or show login
        $router->get('', 'Auth', 'handleAdminRoot');
        
        // Authentication
        $router->get('/login', 'Auth', 'login');
        $router->post('/login', 'Auth', 'authenticate');
        $router->post('/logout', 'Auth', 'logout');

        // Dashboard
        $router->get('/dashboard', 'Dashboard', 'index');

        // User management
        $router->get('/users', 'Users', 'index');
        $router->get('/users/create', 'Users', 'create');
        $router->post('/users/store', 'Users', 'store');
        $router->get('/users/{id}/edit', 'Users', 'edit');
        $router->post('/users/{id}/update', 'Users', 'update');
        $router->post('/users/{id}/delete', 'Users', 'delete');

        // Content management
        $router->get('/content', 'Content', 'index');
        $router->get('/content/drafts', 'Content', 'drafts');
        $router->get('/content/create', 'Content', 'create');
        $router->get('/content/reorder', 'Content', 'reorder');
        $router->post('/content/store', 'Content', 'store');
        $router->post('/content/update-order', 'Content', 'updateOrder');
        $router->post('/content/autosave', 'Content', 'autosave');
        $router->post('/content/load-autosave', 'Content', 'loadAutosave');
        $router->post('/content/list-autosaves', 'Content', 'listAutosaves');
        $router->post('/content/create-draft', 'Content', 'createDraft');
        $router->post('/content/bulk-delete', 'Content', 'bulkDelete');
        $router->get('/content/{id}/edit', 'Content', 'edit');
        $router->post('/content/{id}/update', 'Content', 'update');
        $router->post('/content/{id}/delete', 'Content', 'delete');

        // Autosave management
        $router->get('/autosaves', 'Content', 'autosaves');
        $router->post('/autosaves/{id}/delete', 'Content', 'deleteAutosave');

        // Articles management
        $router->get('/articles', 'Articles', 'index');
        $router->get('/articles/reorder', 'Articles', 'reorder');
        $router->post('/articles/update-order', 'Articles', 'updateOrder');

        // Photobooks management
        $router->get('/photobooks', 'Photobooks', 'index');
        $router->get('/photobooks/reorder', 'Photobooks', 'reorder');
        $router->post('/photobooks/update-order', 'Photobooks', 'updateOrder');

        // **FIXED:** Dedicated image upload routes
        $router->post('/upload/tinymce', 'Upload', 'tinymce');
        $router->post('/upload/dual-image', 'Upload', 'dualImage');

        // Media uploads management
        $router->get('/media', 'Media', 'index');
        $router->get('/media/stats', 'Media', 'stats');
        $router->get('/media/{id}', 'Media', 'view');
        $router->post('/media/{id}/mark-used', 'Media', 'markUsed');
        $router->post('/media/{id}/delete', 'Media', 'delete');
        $router->post('/media/cleanup-orphaned', 'Media', 'cleanupOrphaned');

        // Page management
        $router->get('/pages', 'Pages', 'index');
        $router->get('/pages/create', 'Pages', 'create');
        $router->get('/pages/reorder', 'Pages', 'reorder');
        $router->post('/pages/store', 'Pages', 'store');
        $router->post('/pages/update-order', 'Pages', 'updateOrder');
        $router->get('/pages/{id}/edit', 'Pages', 'edit');
        $router->post('/pages/{id}/update', 'Pages', 'update');
        $router->post('/pages/{id}/delete', 'Pages', 'delete');

        // Settings management
        $router->get('/settings', 'Settings', 'index');
        $router->post('/settings/update', 'Settings', 'update');

        // Menu management
        $router->get('/menus', 'Menus', 'index');
        $router->get('/menus/{id}', 'Menus', 'edit');
        $router->post('/menus/{id}', 'Menus', 'update');
        $router->post('/menus/{id}/items', 'Menus', 'addItem');
        $router->post('/menus/items/{id}/delete', 'Menus', 'deleteItem');
        $router->post('/menus/reorder', 'Menus', 'reorderItems');
    });
};