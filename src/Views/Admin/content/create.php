<?php
/**
 * Content Management - Create View (Fixed)
 * All view-specific JavaScript has been removed to prevent conflicts.
 * The main admin layout now handles all script initialization.
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
        <?php if (!empty($form_errors)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the validation errors below:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($form_errors as $field => $error): ?>
                                <li><?= $this->escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="/admin/content/store" enctype="multipart/form-data" id="contentForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
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
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? '') ?>" pattern="[a-z0-9\-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['url_alias']) ? 'border-red-300' : '' ?>">
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

<!-- Block custom element conflicts immediately -->
<script>
(function() {
    if (window.CE_BLOCKED) return;
    window.CE_BLOCKED = true;
    const orig = window.customElements.define;
    const blocked = new Set();
    window.customElements.define = function(n, c, o) {
        if (blocked.has(n) || window.customElements.get(n)) return;
        blocked.add(n);
        try { orig.call(window.customElements, n, c, o); } catch(e) {}
    };
})();
</script>
<!-- TinyMCE - Only for this page -->
<script src="/assets/js/tinymce-single.js"></script>