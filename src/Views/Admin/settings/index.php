<?php
/**
 * Settings Management - Index View
 * Site settings form with file uploads for logo and favicon
 */
?>

<form method="POST" action="/admin/settings/update" enctype="multipart/form-data" id="settingsForm">
    <?= $this->csrfField() ?>

    <!-- Header Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Site Settings</h2>
                <div class="flex space-x-2">
                    <button type="button" onclick="exportSettings()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Export
                    </button>
                    <button type="button" onclick="clearCache()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Basic Information Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900">Basic Information</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Site Title -->
                <div>
                    <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">
                        Site Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="site_title" id="site_title" required maxlength="100"
                           value="<?= $this->escape($settings['site_title'] ?? '') ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="My Website">
                    <?php if (isset($form_errors['site_title'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['site_title']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Admin Email -->
                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">
                        Admin Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="admin_email" id="admin_email" required maxlength="255"
                           value="<?= $this->escape($settings['admin_email'] ?? '') ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                           placeholder="admin@example.com">
                    <?php if (isset($form_errors['admin_email'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['admin_email']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Site Motto (full width) -->
                <div class="md:col-span-2">
                    <label for="site_motto" class="block text-sm font-medium text-gray-700 mb-1">Site Motto</label>
                    <input type="text" name="site_motto" id="site_motto" maxlength="255"
                           value="<?= $this->escape($settings['site_motto'] ?? '') ?>"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="A short description or tagline for your site">
                    <?php if (isset($form_errors['site_motto'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['site_motto']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Identity Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900">Visual Identity</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Site Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
                    <?php if (!empty($settings['site_logo'])): ?>
                    <div class="mb-3 p-3 border border-gray-200 rounded-md">
                        <div class="flex items-center justify-between">
                            <img src="/uploads/settings/<?= $this->escape($settings['site_logo']) ?>" alt="Current logo" class="h-12 w-auto object-contain">
                            <button type="button" onclick="removeLogo()" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="border-2 border-gray-300 border-dashed rounded-md px-4 py-3 text-center">
                        <input id="site_logo" name="site_logo" type="file" class="sr-only" accept="image/*" onchange="previewLogo(this)">
                        <label for="site_logo" class="cursor-pointer text-sm text-blue-600 hover:text-blue-500">
                            <?= !empty($settings['site_logo']) ? 'Replace logo' : 'Upload logo' ?>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, SVG up to 2MB</p>
                    </div>
                    <div id="logo-preview" style="display: none;" class="mt-3 p-3 border border-gray-200 rounded-md bg-gray-50"></div>
                </div>

                <!-- Favicon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                    <?php if (!empty($settings['favicon'])): ?>
                    <div class="mb-3 p-3 border border-gray-200 rounded-md">
                        <div class="flex items-center justify-between">
                            <img src="/uploads/settings/<?= $this->escape($settings['favicon']) ?>" alt="Current favicon" class="h-8 w-8 object-contain">
                            <button type="button" onclick="removeFavicon()" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="border-2 border-gray-300 border-dashed rounded-md px-4 py-3 text-center">
                        <input id="favicon" name="favicon" type="file" class="sr-only" accept=".ico,.png,.gif,.svg" onchange="previewFavicon(this)">
                        <label for="favicon" class="cursor-pointer text-sm text-blue-600 hover:text-blue-500">
                            <?= !empty($settings['favicon']) ? 'Replace favicon' : 'Upload favicon' ?>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">ICO, PNG, GIF, SVG up to 2MB</p>
                    </div>
                    <div id="favicon-preview" style="display: none;" class="mt-3 p-3 border border-gray-200 rounded-md bg-gray-50"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Preferences Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900">Display Preferences</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                        Timezone <span class="text-red-500">*</span>
                    </label>
                    <select name="timezone" id="timezone" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php foreach ($timezones as $tz): ?>
                        <option value="<?= $this->escape($tz) ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                            <?= $this->escape($tz) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($form_errors['timezone'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['timezone']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Date Format -->
                <div>
                    <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">
                        Date Format <span class="text-red-500">*</span>
                    </label>
                    <select name="date_format" id="date_format" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <?php foreach ($date_formats as $format => $example): ?>
                        <option value="<?= $this->escape($format) ?>" <?= ($settings['date_format'] ?? '') === $format ? 'selected' : '' ?>>
                            <?= $this->escape($example) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($form_errors['date_format'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['date_format']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Items per Page -->
                <div>
                    <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-1">
                        Items per Page <span class="text-red-500">*</span>
                    </label>
                    <select name="items_per_page" id="items_per_page" required class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="5" <?= ($settings['items_per_page'] ?? '10') === '5' ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($settings['items_per_page'] ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($settings['items_per_page'] ?? '10') === '20' ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($settings['items_per_page'] ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                    </select>
                    <?php if (isset($form_errors['items_per_page'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['items_per_page']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Mode Card -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-base font-medium text-gray-900">Maintenance Mode</h3>
        </div>
        <div class="px-6 py-4">
            <!-- Current Status -->
            <?php if (($settings['maintenance_mode'] ?? '0') === '1'): ?>
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <span class="text-sm text-yellow-800 font-medium">Maintenance mode is currently ACTIVE</span>
                </div>
            </div>
            <?php else: ?>
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm text-green-800 font-medium">Site is live and accessible</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Toggle -->
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="maintenance_mode" value="1" 
                           <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Enable maintenance mode</span>
                </label>
            </div>

            <!-- Maintenance Message -->
            <div>
                <label for="maintenance_message" class="block text-sm font-medium text-gray-700 mb-1">
                    Maintenance Message <span class="text-red-500">*</span>
                </label>
                <textarea name="maintenance_message" id="maintenance_message" rows="3" maxlength="1000" required
                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                          placeholder="We are currently performing maintenance on our site. Please check back shortly."><?= $this->escape($settings['maintenance_message'] ?? 'We are currently performing maintenance on our site. Please check back shortly.') ?></textarea>
                <?php if (isset($form_errors['maintenance_message'])): ?>
                <p class="mt-1 text-xs text-red-600"><?= $this->escape($form_errors['maintenance_message']) ?></p>
                <?php else: ?>
                <p class="mt-1 text-xs text-gray-500">Message shown to visitors during maintenance</p>
                <?php endif; ?>
            </div>

            <!-- Preview Button -->
            <div class="mt-3">
                <button type="button" onclick="previewMaintenancePage()" 
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Preview Maintenance Page
                </button>
            </div>
        </div>
    </div>

    <!-- Action Buttons Card -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex space-x-2">
                    <!-- Import Settings -->
                    <div class="relative">
                        <input type="file" id="import_file" accept=".json" onchange="handleImport(this)" class="sr-only">
                        <label for="import_file" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                            Import Settings
                        </label>
                    </div>

                    <!-- Reset to Defaults -->
                    <button type="button" onclick="resetSettings()" class="inline-flex items-center px-3 py-1.5 border border-yellow-300 text-xs font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        Reset to Defaults
                    </button>
                </div>

                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Confirmation Modal -->
<div id="resetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Reset Settings</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to reset all settings to their default values? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form method="POST" action="/admin/settings/reset" class="inline">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-300 mr-2">
                        Reset Settings
                    </button>
                </form>
                <button onclick="closeResetModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// File preview functions
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            preview.innerHTML = `
                <div class="flex items-center justify-between">
                    <img src="${e.target.result}" alt="Logo preview" class="h-12 w-auto object-contain">
                    <button type="button" onclick="clearLogoPreview()" class="text-gray-600 hover:text-gray-800 text-sm">Clear</button>
                </div>`;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    }
}

function previewFavicon(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('favicon-preview');
            preview.innerHTML = `
                <div class="flex items-center justify-between">
                    <img src="${e.target.result}" alt="Favicon preview" class="h-8 w-8 object-contain">
                    <button type="button" onclick="clearFaviconPreview()" class="text-gray-600 hover:text-gray-800 text-sm">Clear</button>
                </div>`;
            preview.style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    }
}

function clearLogoPreview() {
    document.getElementById('site_logo').value = '';
    document.getElementById('logo-preview').style.display = 'none';
}

function clearFaviconPreview() {
    document.getElementById('favicon').value = '';
    document.getElementById('favicon-preview').style.display = 'none';
}

// AJAX file operations
function removeLogo() {
    if (!confirm('Are you sure you want to remove the current logo?')) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('/admin/settings/remove-logo', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the logo.');
    });
}

function removeFavicon() {
    if (!confirm('Are you sure you want to remove the current favicon?')) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('/admin/settings/remove-favicon', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the favicon.');
    });
}

// Settings operations
function exportSettings() {
    window.location.href = '/admin/settings/export';
}

function clearCache() {
    const formData = new FormData();
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('/admin/settings/clear-cache', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while clearing the cache.');
    });
}

function handleImport(input) {
    if (!input.files || !input.files[0]) return;
    
    if (!confirm('Are you sure you want to import settings? This will overwrite current settings.')) {
        input.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('import_file', input.files[0]);
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    fetch('/admin/settings/import', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            alert('Import failed. Please check the file format.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while importing settings.');
    });
}

function resetSettings() {
    document.getElementById('resetModal').classList.remove('hidden');
}

function closeResetModal() {
    document.getElementById('resetModal').classList.add('hidden');
}

function previewMaintenancePage() {
    const message = document.getElementById('maintenance_message').value;
    const siteTitle = document.getElementById('site_title').value || 'Website';
    
    // Create a temporary window with maintenance page preview
    const previewWindow = window.open('', 'maintenancePreview', 'width=800,height=600,scrollbars=yes');
    const previewContent = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance - ${siteTitle}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .maintenance-container {
            background: white;
            border-radius: 8px;
            padding: 3rem 2rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .maintenance-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            color: #111827;
            font-size: 1.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .message {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 2rem;
        }
        
        .admin-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .admin-link:hover {
            background: #2563eb;
        }
        
        .preview-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 6px;
            color: #92400e;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Site Under Maintenance</h1>
        <div class="message">
            ${message.replace(/\n/g, '<br>')}
        </div>
        <a href="/admin/login" class="admin-link">Admin Login</a>
        <div class="preview-notice">
            <strong>PREVIEW MODE</strong> - This is how visitors will see the maintenance page
        </div>
    </div>
</body>
</html>`;
    
    previewWindow.document.write(previewContent);
    previewWindow.document.close();
}

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const requiredFields = ['site_title', 'admin_email', 'timezone', 'date_format', 'items_per_page', 'maintenance_message'];
    
    for (const field of requiredFields) {
        const element = document.getElementById(field);
        if (element && !element.value.trim()) {
            alert('Please fill in all required fields.');
            e.preventDefault();
            element.focus();
            return;
        }
    }
    
    // Email validation
    const email = document.getElementById('admin_email').value;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        alert('Please enter a valid email address.');
        e.preventDefault();
        return;
    }
});

// Close modal on backdrop click
document.getElementById('resetModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResetModal();
    }
});
</script>