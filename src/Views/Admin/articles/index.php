<?php
/**
 * Articles Management - Index View
 * Lists all articles with search, filtering, and management options
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Articles Management</h2>
            <div class="flex space-x-3">
                <a href="/admin/articles/reorder" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                    Reorder Articles
                </a>
                <a href="/admin/content/create?type=article" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Article
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="px-6 py-4 border-b border-gray-200">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-0">
                <input type="text" name="search" value="<?= $this->escape($filters['search']) ?>"
                       placeholder="Search articles..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div class="flex items-center space-x-4">
                <select name="status" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
                <select name="sort_by" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="created_at" <?= $filters['sort_by'] === 'created_at' ? 'selected' : '' ?>>Created Date</option>
                    <option value="title" <?= $filters['sort_by'] === 'title' ? 'selected' : '' ?>>Title</option>
                    <option value="sort_order" <?= $filters['sort_by'] === 'sort_order' ? 'selected' : '' ?>>Order</option>
                </select>
                <select name="sort_dir" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="DESC" <?= $filters['sort_dir'] === 'DESC' ? 'selected' : '' ?>>Descending</option>
                    <option value="ASC" <?= $filters['sort_dir'] === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                </select>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <div class="p-6">
        <?php if (!empty($articles)): ?>
        <!-- Articles Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-2/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                        <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="w-1/4 px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($articles as $article): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 w-10 h-10">
                                    <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 break-words">
                                        <?= $this->escape($article['title']) ?>
                                    </div>
                                    <?php if ($article['teaser']): ?>
                                    <div class="text-sm text-gray-500 break-words">
                                        <?= $this->escape(substr(strip_tags($article['teaser']), 0, 60)) ?>...
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $article['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($article['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #<?= $article['sort_order'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($article['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3 min-w-max">
                                <a href="/admin/content/<?= $article['content_id'] ?>/edit" class="text-blue-600 hover:text-blue-900 whitespace-nowrap">Edit</a>
                                <a href="/articles/<?= $article['url_alias'] ?>" target="_blank" class="text-green-600 hover:text-green-900 whitespace-nowrap">View</a>
                                <form method="POST" action="/admin/content/<?= $article['content_id'] ?>/delete" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this article?')">
                                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 whitespace-nowrap">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <?= ($pagination['current_page'] - 1) * $pagination['items_per_page'] + 1 ?> to
                <?= min($pagination['current_page'] * $pagination['items_per_page'], $pagination['total_items']) ?> of
                <?= $pagination['total_items'] ?> articles
            </div>
            <div class="flex space-x-2">
                <?php if ($pagination['has_prev']): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['prev_page']])) ?>"
                   class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>

                <span class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md">
                    Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
                </span>

                <?php if ($pagination['has_next']): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['next_page']])) ?>"
                   class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No articles found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first article.</p>
            <div class="mt-6">
                <a href="/admin/content/create?type=article" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Article
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>