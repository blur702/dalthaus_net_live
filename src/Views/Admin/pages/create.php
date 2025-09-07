<?php
/**
 * Page Management - Create View (Refactored UI)
 * A simplified, single-column form for creating static pages.
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Create New Page</h2>
        <a href="/admin/pages" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
            Back to Pages
        </a>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/pages/store" id="pageForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token ?? '') ?>">

            <div class="space-y-6">
                <!-- Page Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Page Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="200" value="<?= $this->escape($form_data['title'] ?? '') ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['title']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($form_errors['title'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['title']) ?></p><?php endif; ?>
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? '') ?>" pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['url_alias']) ? 'border-red-300' : '' ?>">
                    <?php if (isset($form_errors['url_alias'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['url_alias']) ?></p><?php else: ?><p class="mt-1 text-sm text-gray-500">Auto-generated from title if left blank.</p><?php endif; ?>
                </div>

                <!-- Page Content -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Page Content <span class="text-red-500">*</span></label>
                    <textarea name="body" id="body" rows="20" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= $this->escape($form_data['body'] ?? '') ?></textarea>
                    <?php if (isset($form_errors['body'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['body']) ?></p><?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                <button type="submit" name="action" value="save_draft" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
    const form = document.getElementById('pageForm');
    if (form) {
        form.addEventListener('submit', function() {
            if (typeof tinymce !== 'undefined' && tinymce.get('body')) {
                tinymce.get('body').save();
            }
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
