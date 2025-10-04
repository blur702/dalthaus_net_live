<?php
/**
 * Content Drafts Management View
 * Shows unfinished auto-saved drafts that can be continued
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Draft Content</h2>
            <p class="text-sm text-gray-600">Unfinished content that can be continued</p>
        </div>
        <div class="flex space-x-2">
            <a href="/admin/content" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path></svg>
                Back to Content
            </a>
            <a href="/admin/content/create?type=article" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Article
            </a>
            <a href="/admin/content/create?type=photobook" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Photobook
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
        <form method="GET" class="flex items-center space-x-4">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" id="type" class="mt-1 block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">All Types</option>
                    <option value="article" <?= $type_filter === 'article' ? 'selected' : '' ?>>Articles</option>
                    <option value="photobook" <?= $type_filter === 'photobook' ? 'selected' : '' ?>>Photobooks</option>
                </select>
            </div>
            <div class="flex-grow"></div>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Apply Filters
            </button>
        </form>
    </div>

    <div class="overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v20c0 4.418 7.163 8 16 8 1.381 0 2.721-.087 4-.252M8 14c0 4.418 7.163 8 16 8s16-3.582 16-8M8 14c0-4.418 7.163-8 16-8s16 3.582 16 8m0 0v14m-16-4c0 4.418 7.163 8 16 8 1.381 0 2.721-.087 4-.252"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No drafts found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <?= !empty($type_filter) ? "No {$type_filter} drafts exist." : "No draft content exists." ?>
                </p>
                <div class="mt-6">
                    <a href="/admin/content/create?type=article" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Create New Content
                    </a>
                </div>
            </div>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Content
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Modified
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($items as $item): ?>
                        <tr class="content-item hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= $this->escape($item['title'] ?: 'Untitled Draft') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php if (!empty($item['teaser'])): ?>
                                                <?= $this->escape(substr($item['teaser'], 0, 100)) ?><?= strlen($item['teaser']) > 100 ? '...' : '' ?>
                                            <?php elseif (!empty($item['body'])): ?>
                                                <?= $this->escape(substr(strip_tags($item['body']), 0, 100)) ?><?= strlen(strip_tags($item['body'])) > 100 ? '...' : '' ?>
                                            <?php else: ?>
                                                <em class="text-yellow-600">Empty draft - auto-saved</em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= $item['content_type'] === 'article' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                    <?= ucfirst($this->escape($item['content_type'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-col">
                                    <span><?= date('M j, Y', strtotime($item['updated_at'])) ?></span>
                                    <span class="text-xs text-gray-400"><?= date('g:i A', strtotime($item['updated_at'])) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="/admin/content/<?= $item['content_id'] ?>/edit" 
                                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Continue Editing
                                    </a>
                                    <form method="POST" action="/admin/content/<?= $item['content_id'] ?>/delete" 
                                          onsubmit="return confirm('Are you sure you want to delete this draft?')" class="inline-block">
                                        <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
                                        <button type="submit" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?= $current_page - 1 ?><?= $type_filter ? "&type={$type_filter}" : '' ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?= $current_page + 1 ?><?= $type_filter ? "&type={$type_filter}" : '' ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium"><?= min(($current_page - 1) * 10 + 1, $total_items) ?></span>
                                to
                                <span class="font-medium"><?= min($current_page * 10, $total_items) ?></span>
                                of
                                <span class="font-medium"><?= $total_items ?></span>
                                drafts
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                                    <?php if ($page === $current_page): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-500">
                                            <?= $page ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?page=<?= $page ?><?= $type_filter ? "&type={$type_filter}" : '' ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <?= $page ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>