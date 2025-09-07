<?php
/**
 * Content Management - Edit View (Fixed)
 * All view-specific JavaScript has been removed to prevent conflicts.
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Edit <?= ucfirst($this->escape($content->getAttribute('content_type'))) ?></h2>
            <p class="text-sm text-gray-600">Editing: "<?= $this->escape($content->getAttribute('title')) ?>"</p>
        </div>
        <div class="flex space-x-2">
            <a href="/admin/content" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
                Back
            </a>
            <a href="/<?= $this->escape($content->getAttribute('content_type')) ?>/<?= $this->escape($content->getAttribute('url_alias')) ?>" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                View Live
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/content/<?= $content->getId() ?>/update" enctype="multipart/form-data" id="contentForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
            <input type="hidden" name="content_type" value="<?= $this->escape($content->getAttribute('content_type')) ?>">

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="200" value="<?= $this->escape($form_data['title'] ?? $content->getAttribute('title')) ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="100" value="<?= $this->escape($form_data['url_alias'] ?? $content->getAttribute('url_alias')) ?>" pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Content Body -->
                <div>
                    <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                    <textarea name="body" id="body" rows="20" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?= $this->escape($form_data['body'] ?? $content->getAttribute('body')) ?></textarea>
                </div>

                <!-- Teaser -->
                <div>
                    <label for="teaser" class="block text-sm font-medium text-gray-700 mb-1">Teaser</label>
                    <textarea name="teaser" id="teaser" rows="3" maxlength="500" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="A brief summary for listings..."><?= $this->escape($form_data['teaser'] ?? $content->getAttribute('teaser')) ?></textarea>
                </div>

                <!-- Images -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                        <?php if ($content->getAttribute('featured_image')): ?>
                            <div class="mb-2"><img src="/uploads/<?= $this->escape($content->getAttribute('featured_image')) ?>" alt="Current featured image" class="h-32 w-auto rounded-md border border-gray-200"></div>
                        <?php endif; ?>
                        <input type="file" name="featured_image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                        <p class="mt-1 text-xs text-gray-500">Upload a new image to replace the current one.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Teaser Image</label>
                        <?php if ($content->getAttribute('teaser_image')): ?>
                            <div class="mb-2"><img src="/uploads/<?= $this->escape($content->getAttribute('teaser_image')) ?>" alt="Current teaser image" class="h-32 w-auto rounded-md border border-gray-200"></div>
                        <?php endif; ?>
                        <input type="file" name="teaser_image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                        <p class="mt-1 text-xs text-gray-500">Upload a new image to replace the current one.</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                <input type="hidden" name="status" id="status" value="<?= $this->escape($content->getAttribute('status')) ?>">
                <button type="submit" name="action" value="save" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Save Changes
                </button>
                <?php if ($content->getAttribute('status') === 'draft'): ?>
                    <button type="submit" name="action" value="publish" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Save & Publish
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="draft" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                        Unpublish (Save as Draft)
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>