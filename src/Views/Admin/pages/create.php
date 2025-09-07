<?php
/**
 * Page Management - Create View (Fixed)
 * All view-specific JavaScript has been removed to prevent conflicts.
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
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">

            <div class="space-y-6">
                <!-- Page Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Page Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="200" value="<?= $this->escape($form_data['title'] ?? '') ?>" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['title']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>" placeholder="Enter page title...">
                    <?php if (isset($form_errors['title'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['title']) ?></p><?php endif; ?>
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? '') ?>" pattern="[a-z0-9\-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['url_alias']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>" placeholder="e.g., about-us">
                    <?php if (isset($form_errors['url_alias'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['url_alias']) ?></p><?php else: ?><p class="mt-1 text-sm text-gray-500">Auto-generated from title if left blank.</p><?php endif; ?>
                </div>

                <!-- Page Content -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Page Content <span class="text-red-500">*</span></label>
                    <textarea name="body" id="body" rows="20" class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="Write your page content here..."><?= $this->escape($form_data['body'] ?? '') ?></textarea>
                    <?php if (isset($form_errors['body'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['body']) ?></p><?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                <button type="submit" name="action" value="save_draft" class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150 ease-in-out shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V2"></path></svg>
                    Save as Draft
                </button>
                <button type="submit" name="action" value="publish" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out shadow-sm">
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