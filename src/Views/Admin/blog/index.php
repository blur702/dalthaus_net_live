<?php
/**
 * Blog Management - Index View
 * List all blog posts with filtering and management options
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Blog Management</h2>
        <a href="/admin/blog/create" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Blog Post
        </a>
    </div>

    <!-- Filters -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-0">
                <input type="text" name="search" placeholder="Search blog posts..." 
                       value="<?= $this->escape($filters['search']) ?>" 
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <select name="status" class="block px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All Status</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            
            <?php if (!empty($all_tags)): ?>
            <div>
                <select name="tag" class="block px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All Tags</option>
                    <?php foreach ($all_tags as $tag): ?>
                        <option value="<?= $this->escape($tag) ?>" <?= $filters['tag'] === $tag ? 'selected' : '' ?>>
                            <?= $this->escape($tag) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Filter
            </button>
            
            <?php if (!empty(array_filter($filters))): ?>
            <a href="/admin/blog" class="inline-flex items-center px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="px-6 py-4">
        <?php if (empty($posts)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No blog posts found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first blog post.</p>
            <div class="mt-6">
                <a href="/admin/blog/create" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent text-sm font-medium rounded-md text-white hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Blog Post
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Blog Posts Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($posts as $post): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <?php if (!empty($post['featured_image'])): ?>
                                <div class="flex-shrink-0 h-10 w-10 mr-3">
                                    <img class="h-10 w-10 rounded-lg object-cover" src="<?= $this->escape($post['featured_image']) ?>" alt="">
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= $this->escape($post['title']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        /blog/<?= $this->escape($post['url_alias']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php if (!empty($post['tags'])): ?>
                                <?php $tags = array_map('trim', explode(',', $post['tags'])); ?>
                                <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                        <?= $this->escape($tag) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($tags) > 3): ?>
                                    <span class="text-xs text-gray-500">+<?= count($tags) - 3 ?> more</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-sm text-gray-400">No tags</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($post['status'] === 'published'): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Published
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Draft
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= $this->escape($post['username']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($post['updated_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <?php if ($post['status'] === 'published'): ?>
                                <a href="/blog/<?= $this->escape($post['url_alias']) ?>" target="_blank" 
                                   class="text-gray-400 hover:text-gray-600" title="View Post">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                
                                <a href="/admin/blog/edit/<?= $post['post_id'] ?>" 
                                   class="text-blue-600 hover:text-blue-900" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                
                                <button onclick="deleteBlogPost(<?= $post['post_id'] ?>, '<?= $this->escape($post['title']) ?>')" 
                                        class="text-red-600 hover:text-red-900" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="mt-6">
            <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
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