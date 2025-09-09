<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/Settings.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\Settings;
use CMS\Utils\Security;

// Check authentication
Auth::requireLogin();

// Get configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $formData = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_motto' => trim($_POST['site_motto'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'timezone' => $_POST['timezone'] ?? '',
            'date_format' => $_POST['date_format'] ?? '',
            'items_per_page' => (int) ($_POST['items_per_page'] ?? 10),
        ];

        // Handle image uploads
        $uploadErrors = [];
        
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $result = handleImageUpload($_FILES['site_logo'], 'logo');
            if ($result['success']) {
                $formData['site_logo'] = $result['filename'];
            } else {
                $uploadErrors[] = 'Logo: ' . $result['error'];
            }
        }

        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $result = handleImageUpload($_FILES['favicon'], 'favicon');
            if ($result['success']) {
                $formData['favicon'] = $result['filename'];
            } else {
                $uploadErrors[] = 'Favicon: ' . $result['error'];
            }
        }

        // Validate settings data
        $validationErrors = Settings::validateSettings($formData);
        
        if (empty($validationErrors) && empty($uploadErrors)) {
            try {
                // Remove empty values to preserve existing ones
                $filteredData = array_filter($formData, function($value) {
                    return $value !== '' && $value !== null;
                });
                
                if (Settings::setMultiple($filteredData)) {
                    $success = 'Settings updated successfully.';
                } else {
                    $error = 'Failed to update settings.';
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

// Get current settings
$currentSettings = Settings::getForAdmin();

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

/**
 * Handle image upload for settings
 */
function handleImageUpload(array $file, string $type): array
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
    
    // Special handling for favicon (should be .ico or small image)
    if ($type === 'favicon') {
        if (!in_array($extension, ['ico', 'png', 'gif'])) {
            return ['success' => false, 'error' => 'Favicon must be .ico, .png, or .gif format.'];
        }
    }
    
    // Generate unique filename
    $filename = $type . '_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <?php include 'includes/header.php'; ?>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Site Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Configure your site's basic information and preferences
            </p>
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

        <!-- Settings Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            
            <!-- Basic Information -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Basic Information</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Basic site information that appears throughout your website.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="grid grid-cols-6 gap-6">
                            <!-- Site Title -->
                            <div class="col-span-6">
                                <label for="site_title" class="block text-sm font-medium text-gray-700">Site Title *</label>
                                <input type="text" name="site_title" id="site_title" required
                                       value="<?= htmlspecialchars($currentSettings['site_title']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">The main title of your website</p>
                            </div>
                            
                            <!-- Site Motto -->
                            <div class="col-span-6">
                                <label for="site_motto" class="block text-sm font-medium text-gray-700">Site Motto/Tagline</label>
                                <input type="text" name="site_motto" id="site_motto"
                                       value="<?= htmlspecialchars($currentSettings['site_motto']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">A brief description or slogan for your site</p>
                            </div>
                            
                            <!-- Admin Email -->
                            <div class="col-span-6">
                                <label for="admin_email" class="block text-sm font-medium text-gray-700">Administrator Email</label>
                                <input type="email" name="admin_email" id="admin_email"
                                       value="<?= htmlspecialchars($currentSettings['admin_email']) ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">Main contact email for the site</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Site Images -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Site Images</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Logo and favicon for your website.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Site Logo -->
                            <div>
                                <label for="site_logo" class="block text-sm font-medium text-gray-700">Site Logo</label>
                                <input type="file" name="site_logo" id="site_logo" accept="image/*"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <?php if (!empty($currentSettings['site_logo'])): ?>
                                    <div class="mt-2">
                                        <img src="/uploads/<?= htmlspecialchars($currentSettings['site_logo']) ?>" 
                                             alt="Current site logo" 
                                             class="h-16 max-w-full object-contain">
                                        <p class="text-xs text-gray-500 mt-1">Current logo</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Favicon -->
                            <div>
                                <label for="favicon" class="block text-sm font-medium text-gray-700">Favicon</label>
                                <input type="file" name="favicon" id="favicon" accept=".ico,.png,.gif"
                                       class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">Preferred: .ico format, 16x16 or 32x32 pixels</p>
                                <?php if (!empty($currentSettings['favicon'])): ?>
                                    <div class="mt-2">
                                        <img src="/uploads/<?= htmlspecialchars($currentSettings['favicon']) ?>" 
                                             alt="Current favicon" 
                                             class="h-8 w-8 object-contain">
                                        <p class="text-xs text-gray-500 mt-1">Current favicon</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Localization & Display -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Localization & Display</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Regional settings and display preferences.
                        </p>
                    </div>
                    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="grid grid-cols-6 gap-6">
                            <!-- Timezone -->
                            <div class="col-span-6 sm:col-span-3">
                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                <select name="timezone" id="timezone"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <?php foreach (Settings::getAvailableTimezones() as $tz => $label): ?>
                                        <option value="<?= htmlspecialchars($tz) ?>" 
                                                <?= $currentSettings['timezone'] === $tz ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Date Format -->
                            <div class="col-span-6 sm:col-span-3">
                                <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format</label>
                                <select name="date_format" id="date_format"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <?php foreach (Settings::getAvailableDateFormats() as $format => $example): ?>
                                        <option value="<?= htmlspecialchars($format) ?>" 
                                                <?= $currentSettings['date_format'] === $format ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($example) ?> (<?= htmlspecialchars($format) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Items Per Page -->
                            <div class="col-span-6 sm:col-span-3">
                                <label for="items_per_page" class="block text-sm font-medium text-gray-700">Items Per Page</label>
                                <select name="items_per_page" id="items_per_page"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <?php foreach ([5, 10, 15, 20, 25, 50] as $num): ?>
                                        <option value="<?= $num ?>" 
                                                <?= (int) $currentSettings['items_per_page'] === $num ? 'selected' : '' ?>>
                                            <?= $num ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Number of items to show per page in listings</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-save -ml-1 mr-2 h-4 w-4"></i>
                    Save Settings
                </button>
            </div>
        </form>

        <!-- Additional Information -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 h-5 w-5"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Settings Information</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Site Title:</strong> Appears in the browser title bar and search results</li>
                            <li><strong>Site Logo:</strong> Recommended size: 200x50 pixels or similar ratio</li>
                            <li><strong>Favicon:</strong> Small icon that appears in browser tabs (16x16 or 32x32 pixels)</li>
                            <li><strong>Timezone:</strong> Affects how dates and times are displayed throughout the site</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const siteTitle = document.getElementById('site_title').value.trim();
        
        if (!siteTitle) {
            e.preventDefault();
            alert('Site title is required.');
            document.getElementById('site_title').focus();
            return false;
        }
    });

    // File upload previews
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById(previewId);
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Add file change listeners for image previews
    document.getElementById('site_logo').addEventListener('change', function() {
        previewImage(this, 'logo-preview');
    });

    document.getElementById('favicon').addEventListener('change', function() {
        previewImage(this, 'favicon-preview');
    });
    </script>
</body>
</html>