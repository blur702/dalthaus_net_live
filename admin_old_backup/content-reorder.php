<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/Content.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\Content;
use CMS\Utils\Security;

// Check authentication
Auth::requireLogin();

// Initialize variables
$error = '';
$success = '';

// Process content type filter
$contentType = $_GET['type'] ?? '';
$validTypes = [Content::TYPE_ARTICLE, Content::TYPE_PHOTOBOOK];

if ($contentType && !in_array($contentType, $validTypes)) {
    $contentType = '';
}

// Handle AJAX sort order update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid security token']);
        exit;
    }

    $orderData = $_POST['order'] ?? [];
    
    if (empty($orderData) || !is_array($orderData)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid order data']);
        exit;
    }

    // Validate and sanitize order data
    $sanitizedOrder = [];
    foreach ($orderData as $item) {
        if (isset($item['id']) && isset($item['position']) && is_numeric($item['id']) && is_numeric($item['position'])) {
            $sanitizedOrder[(int) $item['id']] = (int) $item['position'];
        }
    }

    if (empty($sanitizedOrder)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No valid order data']);
        exit;
    }

    // Update sort order
    $success = Content::updateSortOrder($sanitizedOrder);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// Get content for reordering
$contentList = Content::getForReordering($contentType ?: null);

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Reordering - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <?php include 'includes/header.php'; ?>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="content.php" class="text-gray-700 hover:text-gray-900">
                                <i class="fas fa-file-text mr-2"></i>
                                Content
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-gray-500">Reorder</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Reorder Content
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Drag and drop to reorder content items. Changes are saved automatically.
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="content.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Content
                </a>
            </div>
        </div>

        <!-- Content Type Filter -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Filter by Type</h3>
                    <div class="flex space-x-2">
                        <a href="?type=" 
                           class="<?= empty($contentType) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?> inline-flex items-center px-3 py-2 rounded-full text-sm font-medium hover:bg-blue-50">
                            All Content
                        </a>
                        <a href="?type=<?= Content::TYPE_ARTICLE ?>" 
                           class="<?= $contentType === Content::TYPE_ARTICLE ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?> inline-flex items-center px-3 py-2 rounded-full text-sm font-medium hover:bg-blue-50">
                            <i class="fas fa-newspaper mr-1"></i>
                            Articles
                        </a>
                        <a href="?type=<?= Content::TYPE_PHOTOBOOK ?>" 
                           class="<?= $contentType === Content::TYPE_PHOTOBOOK ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?> inline-flex items-center px-3 py-2 rounded-full text-sm font-medium hover:bg-blue-50">
                            <i class="fas fa-images mr-1"></i>
                            Photobooks
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Alert -->
        <div id="status-alert" class="hidden rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i id="status-icon" class="h-5 w-5"></i>
                </div>
                <div class="ml-3">
                    <p id="status-message" class="text-sm font-medium"></p>
                </div>
            </div>
        </div>

        <!-- Sortable Content List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <?php if (empty($contentList)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-file-text text-gray-400 text-5xl mb-4"></i>
                    <p class="text-lg font-medium text-gray-900">No content found</p>
                    <p class="text-gray-500">
                        <?php if ($contentType): ?>
                            No <?= $contentType ?>s available for reordering.
                        <?php else: ?>
                            No content available for reordering.
                        <?php endif; ?>
                    </p>
                    <div class="mt-6">
                        <a href="content-form.php" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                            Add New Content
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-4 p-3 bg-blue-50 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400 h-5 w-5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Instructions:</strong> Drag and drop the items below to reorder them. 
                                    The new order will be automatically saved and reflected on your public website.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <ul id="sortable-list" class="divide-y divide-gray-200">
                        <?php foreach ($contentList as $index => $content): ?>
                            <li class="sortable-item py-4 cursor-move" data-id="<?= $content->content_id ?>">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 mr-4">
                                        <i class="fas fa-grip-vertical text-gray-400 hover:text-gray-600"></i>
                                    </div>
                                    
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center">
                                            <h3 class="text-lg font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($content->title) ?>
                                            </h3>
                                            
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $content->isArticle() ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                                <?= $content->isArticle() ? 'Article' : 'Photobook' ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mt-1 flex items-center text-sm text-gray-500">
                                            <span>Current order: <span class="current-order font-medium"><?= $content->sort_order ?: ($index + 1) ?></span></span>
                                            <span class="mx-2">•</span>
                                            <span>ID: <?= $content->content_id ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-shrink-0 ml-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="content-form.php?id=<?= $content->content_id ?>" 
                                               class="text-blue-600 hover:text-blue-800 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($content->isPublished()): ?>
                                                <a href="<?= $content->getUrl() ?>" target="_blank" 
                                                   class="text-green-600 hover:text-green-800 transition-colors">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Initialize SortableJS
    const sortableList = document.getElementById('sortable-list');
    let sortable = null;

    if (sortableList) {
        sortable = Sortable.create(sortableList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                updateSortOrder();
            }
        });
    }

    // Update sort order via AJAX
    function updateSortOrder() {
        const items = document.querySelectorAll('.sortable-item');
        const orderData = [];
        
        items.forEach((item, index) => {
            const id = item.dataset.id;
            const position = index + 1;
            
            orderData.push({
                id: parseInt(id),
                position: position
            });
            
            // Update visual order indicator
            const orderSpan = item.querySelector('.current-order');
            if (orderSpan) {
                orderSpan.textContent = position;
            }
        });

        // Show loading state
        showStatus('info', 'Saving new order...', 'fas fa-spinner fa-spin');

        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_order',
                _token: '<?= $csrfToken ?>',
                order: JSON.stringify(orderData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus('success', 'Order updated successfully!', 'fas fa-check-circle');
            } else {
                showStatus('error', data.error || 'Failed to update order', 'fas fa-exclamation-circle');
                // Optionally reload the page to reset order
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showStatus('error', 'Network error occurred', 'fas fa-exclamation-circle');
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        });
    }

    // Show status message
    function showStatus(type, message, iconClass) {
        const alert = document.getElementById('status-alert');
        const icon = document.getElementById('status-icon');
        const messageEl = document.getElementById('status-message');
        
        // Reset classes
        alert.className = 'rounded-md p-4 mb-6';
        icon.className = 'h-5 w-5';
        
        // Add appropriate classes based on type
        switch (type) {
            case 'success':
                alert.classList.add('bg-green-50');
                icon.classList.add('text-green-400');
                messageEl.classList.add('text-green-800');
                break;
            case 'error':
                alert.classList.add('bg-red-50');
                icon.classList.add('text-red-400');
                messageEl.classList.add('text-red-800');
                break;
            case 'info':
                alert.classList.add('bg-blue-50');
                icon.classList.add('text-blue-400');
                messageEl.classList.add('text-blue-800');
                break;
        }
        
        // Set icon and message
        icon.className += ' ' + iconClass;
        messageEl.textContent = message;
        
        // Show alert
        alert.classList.remove('hidden');
        
        // Auto-hide success and info messages
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                alert.classList.add('hidden');
            }, 3000);
        }
    }

    // Add custom CSS for sortable states
    const style = document.createElement('style');
    style.textContent = `
        .sortable-ghost {
            opacity: 0.4;
        }
        
        .sortable-chosen {
            background-color: #f3f4f6;
        }
        
        .sortable-drag {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .sortable-item:hover {
            background-color: #f9fafb;
        }
        
        .cursor-move {
            cursor: move;
        }
        
        .cursor-move:active {
            cursor: grabbing;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>