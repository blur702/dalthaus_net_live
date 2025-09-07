<?php
/**
 * Settings Management - Index View
 * Site settings form with file uploads for logo and favicon
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Site Settings</h2>
            <div class="flex space-x-2">
                <button onclick="exportSettings()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                </button>
                <button onclick="clearCache()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Clear Cache
                </button>
            </div>
        </div>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/settings" enctype="multipart/form-data" id="settingsForm">
            <?= $this->csrfField() ?>

            <!-- Basic Site Information -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Site Title -->
                    <div>
                        <label for="site_title" class="block text-sm font-medium text-gray-700 mb-1">
                            Site Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="site_title" id="site_title" required maxlength="100"
                               value="<?= $this->escape($settings['site_title'] ?? '') ?>"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['site_title']) ? 'border-red-300' : '' ?>">
                        <?php if (isset($form_errors['site_title'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['site_title']) ?></p>
                        <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">The main title of your website</p>
                        <?php endif; ?>
                    </div>

                    <!-- Admin Email -->
                    <div>
                        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">
                            Admin Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="admin_email" id="admin_email" required maxlength="255"
                               value="<?= $this->escape($settings['admin_email'] ?? '') ?>"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['admin_email']) ? 'border-red-300' : '' ?>">
                        <?php if (isset($form_errors['admin_email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['admin_email']) ?></p>
                        <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">Primary contact email for the site</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Site Motto -->
                <div class="mt-6">
                    <label for="site_motto" class="block text-sm font-medium text-gray-700 mb-1">Site Motto</label>
                    <textarea name="site_motto" id="site_motto" rows="2" maxlength="255"
                              class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['site_motto']) ? 'border-red-300' : '' ?>"
                              placeholder="A short description or tagline for your site"><?= $this->escape($settings['site_motto'] ?? '') ?></textarea>
                    <?php if (isset($form_errors['site_motto'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['site_motto']) ?></p>
                    <?php else: ?>
                    <p class="mt-1 text-sm text-gray-500">Optional tagline or description</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Visual Identity -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Visual Identity</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Site Logo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site Logo</label>
                        <div class="space-y-4">
                            <?php if (!empty($settings['site_logo'])): ?>
                            <div class="p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <img src="/uploads/settings/<?= $this->escape($settings['site_logo']) ?>" alt="Current logo" class="h-16 w-auto object-contain">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-700">Current logo</p>
                                        <p class="text-xs text-gray-500"><?= $this->escape($settings['site_logo']) ?></p>
                                    </div>
                                    <button type="button" onclick="removeLogo()" class="text-red-600 hover:text-red-800 text-sm">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors" id="logo-dropzone">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="site_logo" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span><?= !empty($settings['site_logo']) ? 'Replace logo' : 'Upload logo' ?></span>
                                            <input id="site_logo" name="site_logo" type="file" class="sr-only" accept="image/*" onchange="previewLogo(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF, SVG up to 2MB</p>
                                </div>
                            </div>
                            
                            <div id="logo-preview" style="display: none;">
                                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                    <div class="flex items-center space-x-4">
                                        <img id="logo-preview-img" src="" alt="Logo preview" class="h-16 w-auto object-contain">
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700">New logo preview</p>
                                            <p class="text-xs text-gray-500" id="logo-preview-name"></p>
                                        </div>
                                        <button type="button" onclick="clearLogoPreview()" class="text-gray-600 hover:text-gray-800 text-sm">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                        <div class="space-y-4">
                            <?php if (!empty($settings['favicon'])): ?>
                            <div class="p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <img src="/uploads/settings/<?= $this->escape($settings['favicon']) ?>" alt="Current favicon" class="h-8 w-8 object-contain">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-700">Current favicon</p>
                                        <p class="text-xs text-gray-500"><?= $this->escape($settings['favicon']) ?></p>
                                    </div>
                                    <button type="button" onclick="removeFavicon()" class="text-red-600 hover:text-red-800 text-sm">
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors" id="favicon-dropzone">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="favicon" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span><?= !empty($settings['favicon']) ? 'Replace favicon' : 'Upload favicon' ?></span>
                                            <input id="favicon" name="favicon" type="file" class="sr-only" accept=".ico,.png,.gif,.svg" onchange="previewFavicon(this)">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">ICO, PNG, GIF, SVG up to 2MB</p>
                                </div>
                            </div>
                            
                            <div id="favicon-preview" style="display: none;">
                                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                    <div class="flex items-center space-x-4">
                                        <img id="favicon-preview-img" src="" alt="Favicon preview" class="h-8 w-8 object-contain">
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-700">New favicon preview</p>
                                            <p class="text-xs text-gray-500" id="favicon-preview-name"></p>
                                        </div>
                                        <button type="button" onclick="clearFaviconPreview()" class="text-gray-600 hover:text-gray-800 text-sm">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Display Preferences -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Display Preferences</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Timezone -->
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                            Timezone <span class="text-red-500">*</span>
                        </label>
                        <select name="timezone" id="timezone" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['timezone']) ? 'border-red-300' : '' ?>">
                            <?php foreach ($timezones as $tz): ?>
                            <option value="<?= $this->escape($tz) ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                                <?= $this->escape($tz) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['timezone'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['timezone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Date Format -->
                    <div>
                        <label for="date_format" class="block text-sm font-medium text-gray-700 mb-1">
                            Date Format <span class="text-red-500">*</span>
                        </label>
                        <select name="date_format" id="date_format" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['date_format']) ? 'border-red-300' : '' ?>">
                            <?php foreach ($date_formats as $format => $example): ?>
                            <option value="<?= $this->escape($format) ?>" <?= ($settings['date_format'] ?? '') === $format ? 'selected' : '' ?>>
                                <?= $this->escape($example) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($form_errors['date_format'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['date_format']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Items per Page -->
                    <div>
                        <label for="items_per_page" class="block text-sm font-medium text-gray-700 mb-1">
                            Items per Page <span class="text-red-500">*</span>
                        </label>
                        <select name="items_per_page" id="items_per_page" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['items_per_page']) ? 'border-red-300' : '' ?>">
                            <option value="5" <?= ($settings['items_per_page'] ?? '10') === '5' ? 'selected' : '' ?>>5</option>
                            <option value="10" <?= ($settings['items_per_page'] ?? '10') === '10' ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= ($settings['items_per_page'] ?? '10') === '20' ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= ($settings['items_per_page'] ?? '10') === '50' ? 'selected' : '' ?>>50</option>
                        </select>
                        <?php if (isset($form_errors['items_per_page'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['items_per_page']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex space-x-4">
                    <!-- Import Settings -->
                    <div class="relative">
                        <input type="file" id="import_file" accept=".json" onchange="handleImport(this)" class="sr-only">
                        <label for="import_file" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            Import Settings
                        </label>
                    </div>

                    <!-- Reset to Defaults -->
                    <button type="button" onclick="resetSettings()" class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset to Defaults
                    </button>
                </div>

                <div class="flex space-x-3">
                    <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation Modals -->
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
            document.getElementById('logo-preview-img').src = e.target.result;
            document.getElementById('logo-preview-name').textContent = file.name;
            document.getElementById('logo-preview').style.display = 'block';
        };
        
        reader.readAsDataURL(file);
    }
}

function previewFavicon(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('favicon-preview-img').src = e.target.result;
            document.getElementById('favicon-preview-name').textContent = file.name;
            document.getElementById('favicon-preview').style.display = 'block';
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

// Drag and drop functionality
function setupDragDrop(elementId, inputId) {
    const element = document.getElementById(elementId);
    const input = document.getElementById(inputId);
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        element.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        element.addEventListener(eventName, () => element.classList.add('border-blue-400', 'bg-blue-50'), false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        element.addEventListener(eventName, () => element.classList.remove('border-blue-400', 'bg-blue-50'), false);
    });
    
    element.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            input.dispatchEvent(new Event('change'));
        }
    });
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Initialize drag and drop
setupDragDrop('logo-dropzone', 'site_logo');
setupDragDrop('favicon-dropzone', 'favicon');

// Form validation
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const requiredFields = ['site_title', 'admin_email', 'timezone', 'date_format', 'items_per_page'];
    
    for (const field of requiredFields) {
        const value = document.getElementById(field).value.trim();
        if (!value) {
            alert('Please fill in all required fields.');
            e.preventDefault();
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
