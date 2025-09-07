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
$content = null;
$isEdit = false;

// Check if editing existing content
$contentId = (int) ($_GET['id'] ?? 0);
if ($contentId > 0) {
    $content = Content::find($contentId);
    if (!$content) {
        header('Location: content.php');
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
            'content_type' => $_POST['content_type'] ?? Content::TYPE_ARTICLE,
            'status' => $_POST['status'] ?? Content::STATUS_DRAFT,
            'teaser' => trim($_POST['teaser'] ?? ''),
            'body' => $_POST['body'] ?? '',
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'user_id' => Auth::getUserId(),
        ];

        // Handle image uploads
        $uploadErrors = [];
        
        if (isset($_FILES['teaser_image']) && $_FILES['teaser_image']['error'] === UPLOAD_ERR_OK) {
            $result = handleImageUpload($_FILES['teaser_image'], 'teaser_image');
            if ($result['success']) {
                $formData['teaser_image'] = $result['filename'];
            } else {
                $uploadErrors[] = $result['error'];
            }
        }

        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $result = handleImageUpload($_FILES['featured_image'], 'featured_image');
            if ($result['success']) {
                $formData['featured_image'] = $result['filename'];
            } else {
                $uploadErrors[] = $result['error'];
            }
        }

        // Validate data
        $validationErrors = validateContentData($formData, $isEdit ? $contentId : null);
        
        if (empty($validationErrors) && empty($uploadErrors)) {
            try {
                if ($isEdit) {
                    // Update existing content
                    $content->setAttributes($formData);
                    $content->setAttribute('updated_at', date('Y-m-d H:i:s'));
                    
                    if ($formData['status'] === Content::STATUS_PUBLISHED && !$content->getAttribute('published_at')) {
                        $content->setAttribute('published_at', date('Y-m-d H:i:s'));
                    } elseif ($formData['status'] === Content::STATUS_DRAFT) {
                        $content->setAttribute('published_at', null);
                    }
                    
                    if ($content->save()) {
                        $success = 'Content updated successfully.';
                    } else {
                        $error = 'Failed to update content.';
                    }
                } else {
                    // Create new content
                    $formData['created_at'] = date('Y-m-d H:i:s');
                    $formData['updated_at'] = date('Y-m-d H:i:s');
                    $formData['sort_order'] = Content::getNextSortOrder();
                    
                    if ($formData['status'] === Content::STATUS_PUBLISHED) {
                        $formData['published_at'] = date('Y-m-d H:i:s');
                    }
                    
                    $content = Content::create($formData);
                    if ($content) {
                        $success = 'Content created successfully.';
                        $isEdit = true;
                        $contentId = $content->content_id;
                    } else {
                        $error = 'Failed to create content.';
                    }
                }
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please correct the errors below.';
            if (!empty($uploadErrors)) {
                $error .= ' Upload errors: ' . implode(', ', $uploadErrors);
            }
        }
    }
}

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

// Get form values (from content or POST)
$formValues = $content ? $content->getAttributes() : [];
if ($_POST) {
    $formValues = array_merge($formValues, $_POST);
}

/**
 * Handle image upload
 */
function handleImageUpload(array $file, string $field): array
{
    global $config;
    
    $uploadDir = $config['app']['upload_path'];
    $maxSize = $config['app']['max_upload_size'];
    $allowedTypes = $config['app']['allowed_image_types'];
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed size.'];
    }
    
    // Check file type
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

/**
 * Validate content data
 */
function validateContentData(array $data, ?int $excludeId = null): array
{
    $errors = [];
    
    // Title validation
    if (empty($data['title'])) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($data['title']) > 255) {
        $errors['title'] = 'Title must be less than 255 characters';
    }
    
    // URL alias validation
    if (empty($data['url_alias'])) {
        $errors['url_alias'] = 'URL alias is required';
    } elseif (strlen($data['url_alias']) > 255) {
        $errors['url_alias'] = 'URL alias must be less than 255 characters';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $data['url_alias'])) {
        $errors['url_alias'] = 'URL alias can only contain lowercase letters, numbers, and hyphens';
    } else {
        // Check uniqueness
        $db = CMS\Utils\Database::getInstance();
        $query = "SELECT COUNT(*) FROM content WHERE url_alias = ?";
        $params = [$data['url_alias']];
        
        if ($excludeId !== null) {
            $query .= " AND content_id != ?";
            $params[] = $excludeId;
        }
        
        if ($db->fetchColumn($query, $params) > 0) {
            $errors['url_alias'] = 'URL alias is already taken';
        }
    }
    
    // Content type validation
    if (!in_array($data['content_type'], [Content::TYPE_ARTICLE, Content::TYPE_PHOTOBOOK])) {
        $errors['content_type'] = 'Invalid content type';
    }
    
    // Status validation
    if (!in_array($data['status'], [Content::STATUS_DRAFT, Content::STATUS_PUBLISHED])) {
        $errors['status'] = 'Invalid status';
    }
    
    // Meta fields validation
    if (strlen($data['meta_keywords']) > 500) {
        $errors['meta_keywords'] = 'Meta keywords must be less than 500 characters';
    }
    
    if (strlen($data['meta_description']) > 500) {
        $errors['meta_description'] = 'Meta description must be less than 500 characters';
    }
    
    return $errors;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Create' ?> Content - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
                                <span class="text-gray-500"><?= $isEdit ? 'Edit' : 'Create' ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    <?= $isEdit ? 'Edit Content' : 'Create New Content' ?>
                </h2>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="content.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left -ml-1 mr-2 h-4 w-4"></i>
                    Back to Content
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

        <!-- Content Form -->
        <form method="POST" enctype="multipart/form-data" id="content-form" class="space-y-6">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Basic Information</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Essential content details and metadata.
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
                                <input type="text" name="url_alias" id="url_alias" required
                                       value="<?= htmlspecialchars($formValues['url_alias'] ?? '') ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="article-url-slug">
                                <p class="mt-1 text-sm text-gray-500">Lowercase letters, numbers, and hyphens only</p>
                            </div>
                            
                            <!-- Content Type -->
                            <div class="col-span-3">
                                <label for="content_type" class="block text-sm font-medium text-gray-700">Content Type *</label>
                                <select name="content_type" id="content_type" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="<?= Content::TYPE_ARTICLE ?>" <?= ($formValues['content_type'] ?? '') === Content::TYPE_ARTICLE ? 'selected' : '' ?>>Article</option>
                                    <option value="<?= Content::TYPE_PHOTOBOOK ?>" <?= ($formValues['content_type'] ?? '') === Content::TYPE_PHOTOBOOK ? 'selected' : '' ?>>Photobook</option>
                                </select>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-span-3">
                                <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                                <select name="status" id="status" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="<?= Content::STATUS_DRAFT ?>" <?= ($formValues['status'] ?? '') === Content::STATUS_DRAFT ? 'selected' : '' ?>>Draft</option>
                                    <option value="<?= Content::STATUS_PUBLISHED ?>" <?= ($formValues['status'] ?? '') === Content::STATUS_PUBLISHED ? 'selected' : '' ?>>Published</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Content</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            The main content and teaser for your article or photobook.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <!-- Teaser -->
                        <div class="mb-6">
                            <label for="teaser" class="block text-sm font-medium text-gray-700">Teaser</label>
                            <textarea name="teaser" id="teaser" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                      placeholder="Brief description or excerpt"><?= htmlspecialchars($formValues['teaser'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Body -->
                        <div>
                            <label for="body" class="block text-sm font-medium text-gray-700 mb-2">Body Content</label>
                            <textarea name="body" id="body" rows="20"
                                      class="tinymce-editor"><?= htmlspecialchars($formValues['body'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Images</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Upload teaser and featured images for your content.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Teaser Image -->
                            <div>
                                <label for="teaser_image" class="block text-sm font-medium text-gray-700">Teaser Image</label>
                                <input type="file" name="teaser_image" id="teaser_image" accept="image/*"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <?php if ($content && $content->getAttribute('teaser_image')): ?>
                                    <div class="mt-2">
                                        <img src="/uploads/<?= htmlspecialchars($content->getAttribute('teaser_image')) ?>" 
                                             alt="Current teaser image" 
                                             class="h-20 w-20 object-cover rounded-md">
                                        <p class="text-xs text-gray-500 mt-1">Current image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Featured Image -->
                            <div>
                                <label for="featured_image" class="block text-sm font-medium text-gray-700">Featured Image</label>
                                <input type="file" name="featured_image" id="featured_image" accept="image/*"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <?php if ($content && $content->getAttribute('featured_image')): ?>
                                    <div class="mt-2">
                                        <img src="/uploads/<?= htmlspecialchars($content->getAttribute('featured_image')) ?>" 
                                             alt="Current featured image" 
                                             class="h-20 w-20 object-cover rounded-md">
                                        <p class="text-xs text-gray-500 mt-1">Current image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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
                <a href="content.php" 
                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" name="action" value="save_draft"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-save -ml-1 mr-2 h-4 w-4"></i>
                    Save as Draft
                </button>
                <button type="submit" name="action" value="save"
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-check -ml-1 mr-2 h-4 w-4"></i>
                    <?= $isEdit ? 'Update Content' : 'Create Content' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Auto-save notification -->
    <div id="autosave-notification" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg hidden">
        <i class="fas fa-check mr-2"></i>
        Auto-saved
    </div>

    <script>
    // Initialize TinyMCE, but only if it hasn't been initialized for this element already.
    // This prevents conflicts if this form is loaded into a page that already has TinyMCE.
    if (document.querySelector('.tinymce-editor') && (!tinymce.get('body'))) {
        tinymce.init({
            selector: '.tinymce-editor',
            height: 400,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'preview', 'anchor', 'searchreplace', 'visualblocks', 'code',
            'fullscreen', 'insertdatetime', 'media', 'table', 'help',
            'wordcount', 'pagebreak'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | pagebreak | help',
        content_css: '//www.tiny.cloud/css/codepen.min.css',
        pagebreak_separator: '<hr class="mce-pagebreak" />',
        setup: function(editor) {
            editor.on('change', function() {
                // Trigger auto-save on content change
                if (typeof autoSave === 'function') {
                    clearTimeout(window.autoSaveTimer);
                    window.autoSaveTimer = setTimeout(autoSave, 60000); // Auto-save after 60 seconds
                }
            });
        }
    });
    }

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
        const form = document.getElementById('content-form');
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