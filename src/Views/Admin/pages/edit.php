<?php
/**
 * Page Management - Edit View (Fixed)
 * All view-specific JavaScript has been removed to prevent conflicts.
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Edit Page</h2>
            <p class="text-sm text-gray-600">Editing: "<?= $this->escape($page['title']) ?>"</p>
        </div>
        <div class="flex space-x-2">
            <a href="/admin/pages" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
                Back
            </a>
            <a href="/page/<?= $this->escape($page['url_alias']) ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                View Live
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/pages/<?= $page['page_id'] ?>/update" id="pageForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">

            <div class="space-y-6">
                <!-- Page Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Page Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="200" value="<?= $this->escape($form_data['title'] ?? $page['title']) ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? $page['url_alias']) ?>" pattern="[a-z0-9\-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Page Content -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Page Content <span class="text-red-500">*</span></label>
                    <textarea name="body" id="body" rows="20" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= $this->escape($form_data['body'] ?? $page['body']) ?></textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                <button type="submit" name="action" value="save" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Save Changes
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