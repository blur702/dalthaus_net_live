<?php
/**
 * Photobooks Management - Index View
 * Lists all photobooks with search, filtering, and management options
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Photobooks Management</h2>
            <div class="flex space-x-3">
                <a href="/admin/photobooks/reorder" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                    Reorder Photobooks
                </a>
                <a href="/admin/content/create?type=photobook" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Photobook
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="px-6 py-4 border-b border-gray-200">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-0">
                <input type="text" name="search" value="<?= $this->escape($filters['search']) ?>"
                       placeholder="Search photobooks..."
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
        <?php if (!empty($photobooks)): ?>
        <!-- Photobooks Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photobook</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($photobooks as $photobook): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-10 h-10">
                                    <?php if ($photobook['teaser_image']): ?>
                                    <img class="w-10 h-10 rounded object-cover" src="<?= $this->escape($photobook['teaser_image']) ?>" alt="<?= $this->escape($photobook['title']) ?>">
                                    <?php else: ?>
                                    <div class="w-10 h-10 bg-green-100 rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= $this->escape($photobook['title']) ?>
                                    </div>
                                    <?php if ($photobook['teaser']): ?>
                                    <div class="text-sm text-gray-500">
                                        <?= $this->escape(substr(strip_tags($photobook['teaser']), 0, 60)) ?>...
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $photobook['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($photobook['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #<?= $photobook['sort_order'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($photobook['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="/admin/content/<?= $photobook['content_id'] ?>/edit" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <a href="/photobook/<?= $photobook['url_alias'] ?>" target="_blank" class="text-green-600 hover:text-green-900">View</a>
                                <form method="POST" action="/admin/content/<?= $photobook['content_id'] ?>/delete" class="inline"
                                      onsubmit="return confirm('Are you sure you want to delete this photobook?')">
                                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
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
                <?= $pagination['total_items'] ?> photobooks
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No photobooks found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first photobook.</p>
            <div class="mt-6">
                <a href="/admin/content/create?type=photobook" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Photobook
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>