<?php

use CMS\Utils\Router;

return function(Router $router) {
    // Public-facing routes
    $router->group(['namespace' => 'Public'], function(Router $router) {
        $router->get('/', 'Home@index');
        $router->get('/articles', 'Articles@index');
        $router->get('/photobooks', 'Photobooks@index');
        $router->get('/blog', 'Blog@index');
        $router->get('/article/{alias}', 'Articles@show');
        $router->get('/photobook/{alias}', 'Photobooks@show');
        $router->get('/blog/{alias}', 'Blog@show');
        $router->get('/page/{alias}', 'Pages@show');
    });

    // Unprotected admin routes
    $router->group(['prefix' => '/admin', 'namespace' => 'Admin'], function(Router $router) {
        $router->get('/login', 'Auth@login');
        $router->post('/login', 'Auth@authenticate');
    });

    // Protected admin routes
    $router->group(['prefix' => '/admin', 'namespace' => 'Admin', 'middleware' => 'auth'], function(Router $router) {
        $router->get('', 'Auth@handleAdminRoot');
        $router->post('/logout', 'Auth@logout');
        $router->get('/dashboard', 'Dashboard@index');

        // User management
        $router->get('/users', 'Users@index');
        $router->get('/users/create', 'Users@create');
        $router->post('/users/store', 'Users@store');
        $router->get('/users/{id}/edit', 'Users@edit');
        $router->post('/users/{id}/update', 'Users@update');
        $router->post('/users/{id}/delete', 'Users@delete');

        // Content management
        $router->get('/content', 'Content@index');
        $router->get('/content/drafts', 'Content@drafts');
        $router->get('/content/create', 'Content@create');
        $router->get('/content/reorder', 'Content@reorder');
        $router->post('/content/store', 'Content@store');
        $router->post('/content/update-order', 'Content@updateOrder');
        $router->post('/content/autosave', 'Content@autosave');
        $router->post('/content/load-autosave', 'Content@loadAutosave');
        $router->post('/content/list-autosaves', 'Content@listAutosaves');
        $router->post('/content/create-draft', 'Content@createDraft');
        $router->post('/content/bulk-delete', 'Content@bulkDelete');
        $router->get('/content/{id}/edit', 'Content@edit');
        $router->post('/content/{id}/update', 'Content@update');
        $router->post('/content/{id}/delete', 'Content@delete');

        // Autosave management
        $router->get('/autosaves', 'Content@autosaves');
        $router->post('/autosaves/{id}/delete', 'Content@deleteAutosave');

        // Articles management
        $router->get('/articles', 'Articles@index');
        $router->get('/articles/reorder', 'Articles@reorder');
        $router->post('/articles/update-order', 'Articles@updateOrder');

        // Photobooks management
        $router->get('/photobooks', 'Photobooks@index');
        $router->get('/photobooks/reorder', 'Photobooks@reorder');
        $router->post('/photobooks/update-order', 'Photobooks@updateOrder');

        // Blog management
        $router->get('/blog', 'Blog@index');
        $router->get('/blog/create', 'Blog@create');
        $router->get('/blog/reorder', 'Blog@reorder');
        $router->post('/blog/store', 'Blog@store');
        $router->post('/blog/update-order', 'Blog@updateOrder');
        $router->get('/blog/{id}/edit', 'Blog@edit');
        $router->post('/blog/{id}/update', 'Blog@update');
        $router->post('/blog/{id}/delete', 'Blog@delete');

        // Uploads
        $router->post('/upload/tinymce', 'Upload@tinymce');
        $router->post('/upload/dual-image', 'Upload@dualImage');

        // Media management
        $router->get('/media', 'Media@index');
        $router->get('/media/stats', 'Media@stats');
        $router->get('/media/browser', 'Media@browser');
        $router->get('/media/api/list', 'Media@apiList');
        $router->post('/media/api/{id}/metadata', 'Media@apiUpdateMetadata');
        $router->get('/media/{id}', 'Media@view');
        $router->post('/media/{id}/mark-used', 'Media@markUsed');
        $router->post('/media/{id}/delete', 'Media@delete');
        $router->post('/media/cleanup-orphaned', 'Media@cleanupOrphaned');

        // Page management
        $router->get('/pages', 'Pages@index');
        $router->get('/pages/create', 'Pages@create');
        $router->get('/pages/reorder', 'Pages@reorder');
        $router->post('/pages/store', 'Pages@store');
        $router->post('/pages/update-order', 'Pages@updateOrder');
        $router->get('/pages/{id}/edit', 'Pages@edit');
        $router->post('/pages/{id}/update', 'Pages@update');
        $router->post('/pages/{id}/delete', 'Pages@delete');

        // Settings management
        $router->get('/settings', 'Settings@index');
        $router->post('/settings/update', 'Settings@update');

        // Menu management
        $router->get('/menus', 'Menus@index');
        $router->get('/menus/{id}', 'Menus@edit');
        $router->post('/menus/{id}', 'Menus@update');
        $router->post('/menus/{id}/items', 'Menus@addItem');
        $router->post('/menus/items/{id}/delete', 'Menus@deleteItem');
        $router->post('/menus/reorder', 'Menus@reorderItems');
    });
};
