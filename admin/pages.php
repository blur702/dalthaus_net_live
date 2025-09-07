<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/Page.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\Page;
use CMS\Utils\Security;

// Check authentication
Auth::requireLogin();

// Get configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize variables
$error = '';
$success = '';
$page = (int) ($_GET['page'] ?? 1);
$itemsPerPage = $config['app']['items_per_page'];
$offset = ($page - 1) * $itemsPerPage;

// Process filters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'sort_by' => $_GET['sort_by'] ?? 'updated_at',
    'sort_dir' => $_GET['sort_dir'] ?? 'DESC'
];

// Handle page actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $pageId = (int) ($_POST['page_id'] ?? 0);

        switch ($action) {
            case 'delete_page':
                if ($pageId > 0) {
                    $pageObj = Page::find($pageId);
                    if ($pageObj) {
                        if ($pageObj->delete()) {
                            $success = 'Page deleted successfully.';
                        } else {
                            $error = 'Failed to delete page.';
                        }
                    } else {
                        $error = 'Page not found.';
                    }
                }
                break;

            case 'bulk_delete':
                $selectedIds = $_POST['selected_ids'] ?? [];
                
                if (!empty($selectedIds) && is_array($selectedIds)) {
                    $deletedCount = 0;
                    
                    foreach ($selectedIds as $id) {
                        $pageObj = Page::find((int) $id);
                        if ($pageObj && $pageObj->delete()) {
                            $deletedCount++;
                        }
                    }
                    
                    $success = "Successfully deleted {$deletedCount} page(s).";
                } else {
                    $error = 'No pages selected.';
                }
                break;
        }
    }
}

// Get pages data
$pagesList = Page::getForAdmin($filters, $itemsPerPage, $offset);
$totalCount = Page::countForAdmin($filters);
$totalPages = (int) ceil($totalCount / $itemsPerPage);

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <?php include 'includes/header.php'; ?>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Page Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage static pages for your website
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="pages-form.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                    Add New Page
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="rounded-md bg-red-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search Pages</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                               placeholder="Search pages..." 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="sort_by" class="block text-sm font-medium text-gray-700">Sort By</label>
                        <select name="sort_by" id="sort_by" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="updated_at" <?= $filters['sort_by'] === 'updated_at' ? 'selected' : '' ?>>Last Updated</option>
                            <option value="title" <?= $filters['sort_by'] === 'title' ? 'selected' : '' ?>>Title</option>
                            <option value="url_alias" <?= $filters['sort_by'] === 'url_alias' ? 'selected' : '' ?>>URL Alias</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-search -ml-1 mr-2 h-4 w-4"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pages List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <?php if (!empty($pagesList)): ?>
                <form method="POST" id="bulk-form">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                    
                    <!-- Bulk Actions -->
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="select-all" class="ml-2 text-sm text-gray-900">Select All</label>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <button type="submit" name="action" value="bulk_delete" id="bulk-delete" 
                                        class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        disabled onclick="return confirm('Are you sure you want to delete the selected pages?')">
                                    <i class="fas fa-trash -ml-1 mr-2 h-4 w-4"></i>
                                    Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Pages Table -->
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($pagesList as $pageObj): ?>
                            <li class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $pageObj->page_id ?>" 
                                               class="page-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <a href="pages-form.php?id=<?= $pageObj->page_id ?>" 
                                                       class="hover:text-blue-600">
                                                        <?= htmlspecialchars($pageObj->title) ?>
                                                    </a>
                                                </h3>
                                            </div>
                                            
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span class="flex items-center">
                                                    <i class="fas fa-link mr-1"></i>
                                                    /page/<?= htmlspecialchars($pageObj->url_alias) ?>
                                                </span>
                                                <span class="mx-2">•</span>
                                                <span>Updated <?= $pageObj->getFormattedUpdatedDate() ?></span>
                                            </div>
                                            
                                            <?php if ($metaDescription = $pageObj->getAttribute('meta_description')): ?>
                                                <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                                                    <?= htmlspecialchars(substr($metaDescription, 0, 150)) ?><?= strlen($metaDescription) > 150 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= $pageObj->getUrl() ?>" target="_blank" 
                                           class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-external-link-alt h-4 w-4"></i>
                                        </a>
                                        
                                        <a href="pages-form.php?id=<?= $pageObj->page_id ?>" 
                                           class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-edit h-4 w-4"></i>
                                        </a>
                                        
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="delete_page">
                                            <input type="hidden" name="page_id" value="<?= $pageObj->page_id ?>">
                                            <button type="submit" 
                                                    class="inline-flex items-center p-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <i class="fas fa-trash h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </form>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-copy text-gray-400 text-5xl mb-4"></i>
                    <p class="text-lg font-medium text-gray-900">No pages found</p>
                    <p class="text-gray-500">Get started by creating your first static page.</p>
                    <div class="mt-6">
                        <a href="pages-form.php" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                            Add New Page
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6">
                <div class="hidden sm:block">
                    <p class="text-sm text-gray-700">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalCount) ?> of <?= $totalCount ?> results
                    </p>
                </div>
                
                <div class="flex-1 flex justify-between sm:justify-end">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>

    <script>
    // Select all functionality
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.page-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Individual checkbox functionality
    document.querySelectorAll('.page-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkActions);
    });

    // Enable/disable bulk actions
    function toggleBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.page-checkbox:checked');
        const bulkDelete = document.getElementById('bulk-delete');
        bulkDelete.disabled = selectedCheckboxes.length === 0;
    }
    </script>
</body>
</html>