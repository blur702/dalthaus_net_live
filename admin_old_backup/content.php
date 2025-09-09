<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/Content.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\Content;
use CMS\Models\User;
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
    'type' => $_GET['type'] ?? '',
    'status' => $_GET['status'] ?? '',
    'sort_by' => $_GET['sort_by'] ?? 'updated_at',
    'sort_dir' => $_GET['sort_dir'] ?? 'DESC'
];

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $selectedIds = $_POST['selected_ids'] ?? [];
        $bulkAction = $_POST['bulk_action'];

        if (!empty($selectedIds) && is_array($selectedIds)) {
            switch ($bulkAction) {
                case 'delete':
                    $deletedCount = 0;
                    foreach ($selectedIds as $id) {
                        $content = Content::find((int) $id);
                        if ($content && $content->delete()) {
                            $deletedCount++;
                        }
                    }
                    $success = "Successfully deleted {$deletedCount} item(s).";
                    break;

                case 'publish':
                    $publishedCount = 0;
                    foreach ($selectedIds as $id) {
                        $content = Content::find((int) $id);
                        if ($content) {
                            $content->status = Content::STATUS_PUBLISHED;
                            $content->published_at = date('Y-m-d H:i:s');
                            if ($content->save()) {
                                $publishedCount++;
                            }
                        }
                    }
                    $success = "Successfully published {$publishedCount} item(s).";
                    break;

                case 'unpublish':
                    $unpublishedCount = 0;
                    foreach ($selectedIds as $id) {
                        $content = Content::find((int) $id);
                        if ($content) {
                            $content->status = Content::STATUS_DRAFT;
                            $content->published_at = null;
                            if ($content->save()) {
                                $unpublishedCount++;
                            }
                        }
                    }
                    $success = "Successfully unpublished {$unpublishedCount} item(s).";
                    break;
            }
        }
    }
}

// Handle single item actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'];
        $contentId = (int) ($_POST['content_id'] ?? 0);

        if ($contentId > 0) {
            $content = Content::find($contentId);
            
            if ($content) {
                switch ($action) {
                    case 'delete':
                        if ($content->delete()) {
                            $success = 'Content deleted successfully.';
                        } else {
                            $error = 'Failed to delete content.';
                        }
                        break;

                    case 'toggle_status':
                        $content->status = $content->isPublished() ? Content::STATUS_DRAFT : Content::STATUS_PUBLISHED;
                        if ($content->status === Content::STATUS_PUBLISHED) {
                            $content->published_at = date('Y-m-d H:i:s');
                        } else {
                            $content->published_at = null;
                        }
                        
                        if ($content->save()) {
                            $success = 'Content status updated successfully.';
                        } else {
                            $error = 'Failed to update content status.';
                        }
                        break;
                }
            } else {
                $error = 'Content not found.';
            }
        }
    }
}

// Get content data
$contentList = Content::getForAdmin($filters, $itemsPerPage, $offset);
$totalCount = Content::countForAdmin($filters);
$totalPages = (int) ceil($totalCount / $itemsPerPage);

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - Admin</title>
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
                    Content Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage articles and photobooks
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="content-form.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                    Add New Content
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

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                               placeholder="Search content..." 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="type" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="<?= Content::TYPE_ARTICLE ?>" <?= $filters['type'] === Content::TYPE_ARTICLE ? 'selected' : '' ?>>Articles</option>
                            <option value="<?= Content::TYPE_PHOTOBOOK ?>" <?= $filters['type'] === Content::TYPE_PHOTOBOOK ? 'selected' : '' ?>>Photobooks</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="<?= Content::STATUS_PUBLISHED ?>" <?= $filters['status'] === Content::STATUS_PUBLISHED ? 'selected' : '' ?>>Published</option>
                            <option value="<?= Content::STATUS_DRAFT ?>" <?= $filters['status'] === Content::STATUS_DRAFT ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-search -ml-1 mr-2 h-4 w-4"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Content List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
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
                            <select name="bulk_action" id="bulk-action" 
                                    class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Bulk Actions</option>
                                <option value="publish">Publish</option>
                                <option value="unpublish">Unpublish</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" id="bulk-submit" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    disabled>
                                Apply
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Items -->
                <?php if (empty($contentList)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-file-text text-gray-400 text-5xl mb-4"></i>
                        <p class="text-lg font-medium text-gray-900">No content found</p>
                        <p class="text-gray-500">Get started by creating your first article or photobook.</p>
                        <div class="mt-6">
                            <a href="content-form.php" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                                Add New Content
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($contentList as $content): ?>
                            <li class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" name="selected_ids[]" value="<?= $content->content_id ?>" 
                                               class="content-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <a href="content-form.php?id=<?= $content->content_id ?>" 
                                                       class="hover:text-blue-600">
                                                        <?= htmlspecialchars($content->title) ?>
                                                    </a>
                                                </h3>
                                                
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $content->isArticle() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                                    <?= $content->isArticle() ? 'Article' : 'Photobook' ?>
                                                </span>
                                                
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $content->isPublished() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= $content->isPublished() ? 'Published' : 'Draft' ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span>By <?= htmlspecialchars($content->username ?? 'Unknown') ?></span>
                                                <span class="mx-2">•</span>
                                                <span>Updated <?= $content->getFormattedCreatedDate() ?></span>
                                                <?php if ($content->isPublished()): ?>
                                                    <span class="mx-2">•</span>
                                                    <span>Published <?= $content->getFormattedPublishedDate() ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($teaser = $content->getAttribute('teaser')): ?>
                                                <p class="mt-2 text-sm text-gray-600 line-clamp-2">
                                                    <?= htmlspecialchars(substr($teaser, 0, 150)) ?><?= strlen($teaser) > 150 ? '...' : '' ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= $content->getUrl() ?>" target="_blank" 
                                           class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-external-link-alt h-4 w-4"></i>
                                        </a>
                                        
                                        <a href="content-form.php?id=<?= $content->content_id ?>" 
                                           class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-edit h-4 w-4"></i>
                                        </a>
                                        
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="content_id" value="<?= $content->content_id ?>">
                                            <button type="submit" 
                                                    class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-<?= $content->isPublished() ? 'eye-slash' : 'eye' ?> h-4 w-4"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this content?');">
                                            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="content_id" value="<?= $content->content_id ?>">
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
                <?php endif; ?>
            </form>
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
        const checkboxes = document.querySelectorAll('.content-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Individual checkbox functionality
    document.querySelectorAll('.content-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkActions);
    });

    // Enable/disable bulk actions
    function toggleBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.content-checkbox:checked');
        const bulkSubmit = document.getElementById('bulk-submit');
        bulkSubmit.disabled = selectedCheckboxes.length === 0;
    }

    // Bulk action confirmation
    document.getElementById('bulk-form').addEventListener('submit', function(e) {
        const selectedCheckboxes = document.querySelectorAll('.content-checkbox:checked');
        const action = document.getElementById('bulk-action').value;
        
        if (selectedCheckboxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one item.');
            return;
        }
        
        if (!action) {
            e.preventDefault();
            alert('Please select an action.');
            return;
        }
        
        let message = `Are you sure you want to ${action} ${selectedCheckboxes.length} item(s)?`;
        if (action === 'delete') {
            message = `Are you sure you want to delete ${selectedCheckboxes.length} item(s)? This action cannot be undone.`;
        }
        
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>