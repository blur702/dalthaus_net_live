<?php
/**
 * Content Management - Create View (Refactored UI)
 * A simplified, single-column form for creating content.
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Create <?= ucfirst($this->escape($content_type)) ?></h2>
        <a href="/admin/content" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
            Back to Content
        </a>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/content/store" enctype="multipart/form-data" id="contentForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token ?? '') ?>">
            <input type="hidden" name="content_type" value="<?= $this->escape($content_type) ?>">

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="200" value="<?= $this->escape($form_data['title'] ?? '') ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['title']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($form_errors['title'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['title']) ?></p><?php endif; ?>
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? '') ?>" pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['url_alias']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($form_errors['url_alias'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['url_alias']) ?></p><?php else: ?><p class="mt-1 text-sm text-gray-500">Auto-generated from title if left blank.</p><?php endif; ?>
                </div>

                <!-- Content Body -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                    <textarea name="body" id="body" rows="20" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= $this->escape($form_data['body'] ?? '') ?></textarea>
                    <?php if (isset($form_errors['body'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['body']) ?></p><?php endif; ?>
                </div>

                <!-- Teaser -->
                <div>
                    <label for="teaser" class="block text-sm font-medium text-gray-700 mb-1">Teaser</label>
                    <textarea name="teaser" id="teaser" rows="3" maxlength="500" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="A brief summary for listings..."><?= $this->escape($form_data['teaser'] ?? '') ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">A short description shown on listing pages.</p>
                </div>

                <!-- Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="featured_image_upload" class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                        <input type="file" name="featured_image" id="featured_image_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    </div>
                    <div>
                        <label for="teaser_image_upload" class="block text-sm font-medium text-gray-700 mb-1">Teaser Image</label>
                        <input type="file" name="teaser_image" id="teaser_image_upload" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                <input type="hidden" name="status" id="status" value="draft">
                <button type="submit" name="action" value="draft" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Save as Draft
                </button>
                <button type="submit" name="action" value="publish" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Create & Publish
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contentForm');
    
    // File size validation (max 5MB per file)
    const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    const fileInputs = form.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > maxFileSize) {
                    alert(`File "${file.name}" is too large. Maximum size is 5MB. Your file is ${(file.size / 1024 / 1024).toFixed(2)}MB.`);
                    this.value = ''; // Clear the input
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert(`File "${file.name}" is not a supported image type. Please use JPG, PNG, GIF, or WebP.`);
                    this.value = ''; // Clear the input
                    return;
                }
            }
        });
    });
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Check total form size before submitting
            let totalSize = 0;
            fileInputs.forEach(input => {
                if (input.files.length > 0) {
                    totalSize += input.files[0].size;
                }
            });
            
            // Check if total size exceeds 20MB
            const maxTotalSize = 20 * 1024 * 1024; // 20MB
            if (totalSize > maxTotalSize) {
                e.preventDefault();
                alert(`Total file size (${(totalSize / 1024 / 1024).toFixed(2)}MB) exceeds the maximum allowed size of 20MB. Please reduce file sizes or upload fewer files.`);
                return false;
            }
            
            if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                tinymce.get('body').save();
            }
            const action = e.submitter ? e.submitter.value : 'draft';
            document.getElementById('status').value = action;
        });
    }

    const titleInput = document.getElementById('title');
    const urlAliasInput = document.getElementById('url_alias');
    if (titleInput && urlAliasInput) {
        titleInput.addEventListener('input', function() {
            if (!urlAliasInput.dataset.manuallyEdited) {
                urlAliasInput.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim('-');
            }
        });
        urlAliasInput.addEventListener('input', () => urlAliasInput.dataset.manuallyEdited = 'true');
    }
});
</script>
