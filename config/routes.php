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
        $router->get('/content/create', 'Content', 'create');
        $router->post('/content/store', 'Content', 'store');
        $router->get('/content/{id}/edit', 'Content', 'edit');
        $router->post('/content/{id}/update', 'Content', 'update');
        $router->post('/content/{id}/delete', 'Content', 'delete');
        $router->get('/reorder', 'Content', 'reorder');
        $router->post('/reorder', 'Content', 'updateOrder');
        $router->post('/content/autosave', 'Content', 'autosave');

        // **FIXED:** Dedicated image upload route
        $router->post('/upload/tinymce', 'Upload', 'tinymce');

        // Page management
        $router->get('/pages', 'Pages', 'index');
        $router->get('/pages/create', 'Pages', 'create');
        $router->post('/pages/store', 'Pages', 'store');
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