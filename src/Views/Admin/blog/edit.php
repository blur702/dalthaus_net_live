<?php
/**
 * Blog Management - Edit View
 * Edit existing blog post with word counter and visual indicators
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Edit Blog Post</h2>
        <div class="flex items-center space-x-4">
            <?php if ($post['status'] === 'published'): ?>
            <a href="/blog/<?= $this->escape($post['url_alias']) ?>" target="_blank" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                View Post
            </a>
            <?php endif; ?>
            <a href="/admin/blog" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
                Back to Blog
            </a>
        </div>
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
        
        <form method="POST" action="/admin/blog/update/<?= $post['post_id'] ?>" enctype="multipart/form-data" id="blogEditForm">
            <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required maxlength="255" value="<?= $this->escape($post['title']) ?>" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['title']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>" placeholder="Enter blog post title...">
                    <?php if (isset($form_errors['title'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['title']) ?></p><?php endif; ?>
                </div>

                <!-- URL Alias -->
                <div>
                    <label for="url_alias" class="block text-sm font-medium text-gray-700 mb-1">URL Alias <span class="text-red-500">*</span></label>
                    <input type="text" name="url_alias" id="url_alias" required maxlength="255" value="<?= $this->escape($post['url_alias']) ?>" pattern="[a-z0-9\-]+" title="Only lowercase letters, numbers, and hyphens allowed" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['url_alias']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>" placeholder="e.g., my-blog-post-title">
                    <?php if (isset($form_errors['url_alias'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['url_alias']) ?></p><?php endif; ?>
                </div>

                <!-- Excerpt -->
                <div>
                    <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                    <textarea name="excerpt" id="excerpt" rows="3" maxlength="500" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="A brief summary for listings..."><?= $this->escape($post['excerpt'] ?? '') ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">A short description shown on blog listing pages.</p>
                </div>

                <!-- Body Content with Word Counter -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="body" class="block text-sm font-medium text-gray-700">Content <span class="text-red-500">*</span></label>
                        <div class="flex items-center space-x-2">
                            <div id="wordCountAlert" class="hidden">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div id="wordCount" class="text-sm font-medium">
                                <span id="wordCountNumber">0</span> words
                                <span id="wordCountStatus" class="text-gray-500"></span>
                            </div>
                        </div>
                    </div>
                    <textarea name="body" id="body" rows="20" required class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="Write your blog post content here..."><?= $this->escape($post['body']) ?></textarea>
                    <div class="mt-2 flex justify-between items-center text-sm text-gray-500">
                        <span>Content length recommendations: 300+ words for SEO optimization</span>
                        <div id="wordCountProgressBar" class="w-32 bg-gray-200 rounded-full h-2">
                            <div id="wordCountProgress" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                    <?php if (isset($form_errors['body'])): ?><p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['body']) ?></p><?php endif; ?>
                </div>

                <!-- Tags -->
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                    <input type="text" name="tags" id="tags" maxlength="500" value="<?= $this->escape($post['tags'] ?? '') ?>" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="e.g., photography, tips, tutorial">
                    <p class="mt-1 text-sm text-gray-500">Separate multiple tags with commas.</p>
                </div>

                <!-- Featured Image -->
                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                    
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="mb-3 p-3 border border-gray-200 rounded-lg bg-gray-50">
                        <div class="flex items-center space-x-3">
                            <img src="<?= $this->escape($post['featured_image']) ?>" alt="Current featured image" class="w-16 h-16 object-cover rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Current Image</p>
                                <p class="text-sm text-gray-500"><?= basename($post['featured_image']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <input type="file" name="featured_image" id="featured_image" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-1 text-sm text-gray-500">
                        <?php if (!empty($post['featured_image'])): ?>
                            Upload a new image to replace the current one (max 5MB).
                        <?php else: ?>
                            Upload an image for the blog post (max 5MB).
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Related Content -->
                <?php if (!empty($available_content)): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Related Articles/Photobooks</label>
                    <div class="mt-2 space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        <?php foreach ($available_content as $content): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="related_content[]" value="<?= $content['content_id'] ?>" 
                                   <?= in_array($content['content_id'], $related_content_ids) ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">
                                <?= $this->escape($content['title']) ?> 
                                <span class="text-gray-500">(<?= ucfirst($content['content_type']) ?>)</span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Select articles or photobooks related to this blog post.</p>
                </div>
                <?php endif; ?>

                <!-- SEO Meta Fields -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">SEO & Metadata</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" name="meta_title" id="meta_title" maxlength="60" value="<?= $this->escape($post['meta_title'] ?? '') ?>" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="Custom title for search engines">
                            <p class="mt-1 text-sm text-gray-500">Leave blank to use the blog post title.</p>
                        </div>
                        
                        <div>
                            <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="meta_description" id="meta_description" rows="2" maxlength="160" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="Description for search engines (160 characters max)"><?= $this->escape($post['meta_description'] ?? '') ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Leave blank to auto-generate from excerpt or content.</p>
                        </div>
                        
                        <div>
                            <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" id="meta_keywords" value="<?= $this->escape($post['meta_keywords'] ?? '') ?>" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400" placeholder="comma, separated, keywords">
                            <p class="mt-1 text-sm text-gray-500">Leave blank to use tags as keywords.</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm bg-white hover:border-gray-400">
                        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>

                <!-- Post Meta Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Post Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">Created:</span><br>
                            <?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
                        </div>
                        <div>
                            <span class="font-medium">Last Updated:</span><br>
                            <?= date('M j, Y \a\t g:i A', strtotime($post['updated_at'])) ?>
                        </div>
                        <?php if (!empty($post['published_at'])): ?>
                        <div>
                            <span class="font-medium">Published:</span><br>
                            <?= date('M j, Y \a\t g:i A', strtotime($post['published_at'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button type="button" onclick="deleteBlogPost(<?= $post['post_id'] ?>, '<?= $this->escape($post['title']) ?>')" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete Post
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <a href="/admin/blog" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update Blog Post
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Word counter functionality with visual indicators - same as create page
document.addEventListener('DOMContentLoaded', function() {
    const bodyField = document.getElementById('body');
    const wordCountNumber = document.getElementById('wordCountNumber');
    const wordCountStatus = document.getElementById('wordCountStatus');
    const wordCountAlert = document.getElementById('wordCountAlert');
    const wordCountProgress = document.getElementById('wordCountProgress');
    
    function updateWordCount() {
        const text = bodyField.value.trim();
        const words = text === '' ? 0 : text.split(/\s+/).length;
        
        wordCountNumber.textContent = words;
        
        // Update progress bar (max at 500 words for visual purposes)
        const progressPercentage = Math.min((words / 500) * 100, 100);
        wordCountProgress.style.width = progressPercentage + '%';
        
        // Update status and styling based on word count
        if (words < 50) {
            wordCountStatus.textContent = '(Very short)';
            wordCountStatus.className = 'text-red-500';
            wordCountProgress.className = 'bg-red-500 h-2 rounded-full transition-all duration-300';
            wordCountAlert.classList.add('hidden');
        } else if (words < 150) {
            wordCountStatus.textContent = '(Short)';
            wordCountStatus.className = 'text-orange-500';
            wordCountProgress.className = 'bg-orange-500 h-2 rounded-full transition-all duration-300';
            wordCountAlert.classList.add('hidden');
        } else if (words < 300) {
            wordCountStatus.textContent = '(Good length)';
            wordCountStatus.className = 'text-yellow-500';
            wordCountProgress.className = 'bg-yellow-500 h-2 rounded-full transition-all duration-300';
            wordCountAlert.classList.add('hidden');
        } else if (words >= 400) {
            wordCountStatus.textContent = '(Long - consider breaking up)';
            wordCountStatus.className = 'text-amber-600';
            wordCountProgress.className = 'bg-amber-500 h-2 rounded-full transition-all duration-300';
            wordCountAlert.classList.remove('hidden');
        } else {
            wordCountStatus.textContent = '(Optimal length)';
            wordCountStatus.className = 'text-green-500';
            wordCountProgress.className = 'bg-green-500 h-2 rounded-full transition-all duration-300';
            wordCountAlert.classList.add('hidden');
        }
    }
    
    // Update word count on input
    bodyField.addEventListener('input', updateWordCount);
    
    // Initial count
    updateWordCount();
});

// Delete post function
function deleteBlogPost(postId, title) {
    if (confirm('Are you sure you want to delete the blog post "' + title + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/blog/delete/' + postId;
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '<?= $csrf_token ?>';
        form.appendChild(tokenInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>