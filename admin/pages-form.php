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

// Initialize variables
$error = '';
$success = '';
$pageObj = null;
$isEdit = false;

// Check if editing existing page
$pageId = (int) ($_GET['id'] ?? 0);
if ($pageId > 0) {
    $pageObj = Page::find($pageId);
    if (!$pageObj) {
        header('Location: pages.php');
        exit;
    }
    $isEdit = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $formData = [
            'title' => trim($_POST['title'] ?? ''),
            'url_alias' => trim($_POST['url_alias'] ?? ''),
            'body' => $_POST['body'] ?? '',
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
        ];

        // Validate data
        $validationErrors = Page::validatePageData($formData, $isEdit ? $pageId : null);
        
        if (empty($validationErrors)) {
            try {
                if ($isEdit) {
                    // Update existing page
                    $pageObj->setAttributes($formData);
                    $pageObj->setAttribute('updated_at', date('Y-m-d H:i:s'));
                    
                    if ($pageObj->save()) {
                        $success = 'Page updated successfully.';
                    } else {
                        $error = 'Failed to update page.';
                    }
                } else {
                    // Create new page
                    $formData['updated_at'] = date('Y-m-d H:i:s');
                    
                    $pageObj = Page::create($formData);
                    if ($pageObj) {
                        $success = 'Page created successfully.';
                        $isEdit = true;
                        $pageId = $pageObj->page_id;
                    } else {
                        $error = 'Failed to create page.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please correct the errors below.';
        }
    }
}

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

// Get form values (from page or POST)
$formValues = $pageObj ? $pageObj->getAttributes() : [];
if ($_POST) {
    $formValues = array_merge($formValues, $_POST);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Create' ?> Page - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- TinyMCE -->
    <!-- TinyMCE will be loaded from admin layout -->
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
                            <a href="pages.php" class="text-gray-700 hover:text-gray-900">
                                <i class="fas fa-copy mr-2"></i>
                                Pages
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-gray-500"><?= $isEdit ? 'Edit' : 'Create' ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    <?= $isEdit ? 'Edit Page' : 'Create New Page' ?>
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="pages.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Pages
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
                        <?php if (isset($validationErrors) && !empty($validationErrors)): ?>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($validationErrors as $field => $message): ?>
                                    <li><?= htmlspecialchars($message) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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

        <!-- Page Form -->
        <form method="POST" id="page-form" class="space-y-6">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Page Information</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Basic page details and URL settings.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="grid grid-cols-6 gap-6">
                            <!-- Title -->
                            <div class="col-span-6">
                                <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                                <input type="text" name="title" id="title" required
                                       value="<?= htmlspecialchars($formValues['title'] ?? '') ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            
                            <!-- URL Alias -->
                            <div class="col-span-6">
                                <label for="url_alias" class="block text-sm font-medium text-gray-700">URL Alias *</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        /page/
                                    </span>
                                    <input type="text" name="url_alias" id="url_alias" required
                                           value="<?= htmlspecialchars($formValues['url_alias'] ?? '') ?>"
                                           class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           placeholder="page-url-slug">
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Lowercase letters, numbers, and hyphens only</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Page Content</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            The main content of your page.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <label for="body" class="block text-sm font-medium text-gray-700 mb-2">Body Content</label>
                        <textarea name="body" id="body" rows="20"
                                  class="tinymce-editor"><?= htmlspecialchars($formValues['body'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">SEO Meta Data</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Search engine optimization metadata.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <!-- Meta Keywords -->
                        <div class="mb-6">
                            <label for="meta_keywords" class="block text-sm font-medium text-gray-700">Meta Keywords</label>
                            <input type="text" name="meta_keywords" id="meta_keywords"
                                   value="<?= htmlspecialchars($formValues['meta_keywords'] ?? '') ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="keyword1, keyword2, keyword3">
                        </div>
                        
                        <!-- Meta Description -->
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description</label>
                            <textarea name="meta_description" id="meta_description" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                      placeholder="Brief description for search engines (max 160 characters)"><?= htmlspecialchars($formValues['meta_description'] ?? '') ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Recommended length: 150-160 characters</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                <a href="pages.php" 
                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save -ml-1 mr-2 h-4 w-4"></i>
                    <?= $isEdit ? 'Update Page' : 'Create Page' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Auto-save notification -->
    <div id="autosave-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg hidden">
        <i class="fas fa-check mr-2"></i>
        Auto-saved
    </div>

    <!-- TinyMCE initialization handled centrally -->
    
    <script>
    // TinyMCE will be initialized automatically

    // Auto-generate URL alias from title
    document.getElementById('title').addEventListener('input', function() {
        const title = this.value;
        const alias = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s-]+/g, '-')
            .replace(/^-+|-+$/g, '');
        
        const urlAliasField = document.getElementById('url_alias');
        if (!urlAliasField.dataset.userModified) {
            urlAliasField.value = alias;
        }
    });

    // Mark URL alias as user-modified when manually changed
    document.getElementById('url_alias').addEventListener('input', function() {
        this.dataset.userModified = 'true';
    });

    // Auto-save functionality
    function autoSave() {
        const form = document.getElementById('page-form');
        const formData = new FormData(form);
        formData.append('action', 'autosave');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAutoSaveNotification();
            }
        })
        .catch(error => {
            console.error('Auto-save failed:', error);
        });
    }

    // Show auto-save notification
    function showAutoSaveNotification() {
        const notification = document.getElementById('autosave-notification');
        notification.classList.remove('hidden');
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 3000);
    }

    // Initialize auto-save timer
    window.autoSaveTimer = null;
    
    // Set up auto-save for form inputs
    document.querySelectorAll('input, textarea, select').forEach(element => {
        if (element.id !== 'body') { // TinyMCE handles its own change events
            element.addEventListener('input', function() {
                clearTimeout(window.autoSaveTimer);
                window.autoSaveTimer = setTimeout(autoSave, 60000);
            });
        }
    });

    // Character counter for meta description
    document.getElementById('meta_description').addEventListener('input', function() {
        const length = this.value.length;
        const maxLength = 160;
        const parentDiv = this.parentElement;
        
        let counter = parentDiv.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('p');
            counter.className = 'char-counter mt-1 text-sm';
            parentDiv.appendChild(counter);
        }
        
        counter.textContent = `${length}/${maxLength} characters`;
        counter.className = `char-counter mt-1 text-sm ${length > maxLength ? 'text-red-500' : 'text-gray-500'}`;
    });
    </script>
</body>
</html>