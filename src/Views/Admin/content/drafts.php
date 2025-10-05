<?php
/**
 * Content Drafts Management View
 * Shows unfinished auto-saved drafts that can be continued
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Auto-save & Draft Management</h2>
                <p class="text-sm text-gray-600">Manage auto-saved drafts and unfinished content</p>
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
        
        <!-- Auto-save Analytics Bar -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-900">Total Drafts</p>
                        <p class="text-lg font-semibold text-blue-600"><?= $total_items ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-yellow-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-900">Recent Activity</p>
                        <p class="text-lg font-semibold text-yellow-600"><?= $recent_drafts_count ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-900">Ready to Publish</p>
                        <p class="text-lg font-semibold text-green-600"><?= $ready_to_publish_count ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-red-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-900">Old Drafts</p>
                        <p class="text-lg font-semibold text-red-600"><?= $old_drafts_count ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filter and Bulk Actions Bar -->
    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
        <form method="GET" class="flex items-center space-x-4 mb-4">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Content Type</label>
                <select name="type" id="type" class="mt-1 block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">All Types</option>
                    <option value="article" <?= $type_filter === 'article' ? 'selected' : '' ?>>Articles</option>
                    <option value="photobook" <?= $type_filter === 'photobook' ? 'selected' : '' ?>>Photobooks</option>
                </select>
            </div>
            <div>
                <label for="age" class="block text-sm font-medium text-gray-700">Draft Age</label>
                <select name="age" id="age" class="mt-1 block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="">All Ages</option>
                    <option value="recent" <?= ($age_filter ?? '') === 'recent' ? 'selected' : '' ?>>Last 24 Hours</option>
                    <option value="week" <?= ($age_filter ?? '') === 'week' ? 'selected' : '' ?>>Last Week</option>
                    <option value="month" <?= ($age_filter ?? '') === 'month' ? 'selected' : '' ?>>Last Month</option>
                    <option value="old" <?= ($age_filter ?? '') === 'old' ? 'selected' : '' ?>>Older than Month</option>
                </select>
            </div>
            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700">Sort By</label>
                <select name="sort" id="sort" class="mt-1 block w-40 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="updated_at" <?= ($sort_filter ?? 'updated_at') === 'updated_at' ? 'selected' : '' ?>>Last Modified</option>
                    <option value="created_at" <?= ($sort_filter ?? '') === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                    <option value="title" <?= ($sort_filter ?? '') === 'title' ? 'selected' : '' ?>>Title</option>
                </select>
            </div>
            <div class="flex-grow"></div>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Apply Filters
            </button>
        </form>
        
        <!-- Bulk Actions -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="select-all" class="form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2 text-sm text-gray-700">Select All</span>
                </label>
                <span id="selected-count" class="text-sm text-gray-500">0 selected</span>
            </div>
            <div id="bulk-actions" class="flex items-center space-x-2 opacity-0 transition-opacity duration-200">
                <button type="button" id="bulk-delete" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected
                </button>
                <button type="button" id="bulk-publish" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Mark as Ready
                </button>
            </div>
        </div>
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
                            <input type="checkbox" id="header-checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Content & Auto-save Info
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Auto-save Activity
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($items as $item): ?>
                        <?php 
                            $isEmptyDraft = empty($item['title']) && empty($item['teaser']) && empty($item['body']);
                            $isRecentlyModified = (time() - strtotime($item['updated_at'])) < 3600; // 1 hour
                            $timeSinceCreated = time() - strtotime($item['created_at']);
                            $timeSinceModified = time() - strtotime($item['updated_at']);
                            $autosaveFrequency = $timeSinceCreated > 0 ? round(($timeSinceModified < $timeSinceCreated ? $timeSinceCreated - $timeSinceModified : 0) / 60, 1) : 0;
                        ?>
                        <tr class="content-item hover:bg-gray-50" data-content-id="<?= $item['content_id'] ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 item-checkbox" value="<?= $item['content_id'] ?>">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full <?= $isEmptyDraft ? 'bg-orange-100' : ($isRecentlyModified ? 'bg-green-100' : 'bg-yellow-100') ?> flex items-center justify-center">
                                            <?php if ($isEmptyDraft): ?>
                                                <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                                </svg>
                                            <?php elseif ($isRecentlyModified): ?>
                                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= $this->escape($item['title'] ?: 'Untitled Draft') ?>
                                            <?php if ($isEmptyDraft): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                                    Auto-saved only
                                                </span>
                                            <?php elseif ($isRecentlyModified): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Recently active
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php if (!empty($item['teaser'])): ?>
                                                <?= $this->escape(substr($item['teaser'], 0, 100)) ?><?= strlen($item['teaser']) > 100 ? '...' : '' ?>
                                            <?php elseif (!empty($item['body'])): ?>
                                                <?= $this->escape(substr(strip_tags($item['body']), 0, 100)) ?><?= strlen(strip_tags($item['body'])) > 100 ? '...' : '' ?>
                                            <?php else: ?>
                                                <em class="text-orange-600">Empty draft - created by auto-save</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            Content ID: <?= $item['content_id'] ?> • Created: <?= date('M j, g:i A', strtotime($item['created_at'])) ?>
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
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium"><?= date('M j, Y', strtotime($item['updated_at'])) ?></span>
                                        <?php if ($isRecentlyModified): ?>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-gray-400"><?= date('g:i A', strtotime($item['updated_at'])) ?></span>
                                    <div class="text-xs text-gray-400 mt-1">
                                        <?php if ($timeSinceModified < 60): ?>
                                            Last save: <?= $timeSinceModified ?>s ago
                                        <?php elseif ($timeSinceModified < 3600): ?>
                                            Last save: <?= round($timeSinceModified / 60) ?>m ago
                                        <?php elseif ($timeSinceModified < 86400): ?>
                                            Last save: <?= round($timeSinceModified / 3600) ?>h ago
                                        <?php else: ?>
                                            Last save: <?= round($timeSinceModified / 86400) ?>d ago
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($timeSinceCreated != $timeSinceModified && $timeSinceCreated > 0): ?>
                                        <div class="text-xs text-blue-600 mt-1">
                                            Auto-save session: <?= round(abs($timeSinceCreated - $timeSinceModified) / 60) ?>min
                                        </div>
                                    <?php endif; ?>
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

<!-- Bulk Actions JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const headerCheckbox = document.getElementById('header-checkbox');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectedCount = document.getElementById('selected-count');
    const bulkActions = document.getElementById('bulk-actions');
    const bulkDeleteBtn = document.getElementById('bulk-delete');
    const bulkPublishBtn = document.getElementById('bulk-publish');
    
    function updateSelectionUI() {
        const selectedItems = document.querySelectorAll('.item-checkbox:checked');
        const count = selectedItems.length;
        
        selectedCount.textContent = `${count} selected`;
        
        if (count > 0) {
            bulkActions.classList.remove('opacity-0');
            bulkActions.classList.add('opacity-100');
        } else {
            bulkActions.classList.add('opacity-0');
            bulkActions.classList.remove('opacity-100');
        }
        
        // Update header checkbox state
        if (count === 0) {
            headerCheckbox.checked = false;
            headerCheckbox.indeterminate = false;
        } else if (count === itemCheckboxes.length) {
            headerCheckbox.checked = true;
            headerCheckbox.indeterminate = false;
        } else {
            headerCheckbox.checked = false;
            headerCheckbox.indeterminate = true;
        }
    }
    
    // Handle select all functionality
    [selectAllCheckbox, headerCheckbox].forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            itemCheckboxes.forEach(item => {
                item.checked = this.checked;
            });
            updateSelectionUI();
        });
    });
    
    // Handle individual item selection
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionUI);
    });
    
    // Handle bulk delete
    bulkDeleteBtn.addEventListener('click', function() {
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'));
        const count = selectedItems.length;
        
        if (count === 0) return;
        
        if (confirm(`Are you sure you want to delete ${count} draft(s)? This action cannot be undone.`)) {
            const contentIds = selectedItems.map(item => item.value);
            
            // Create form for bulk delete
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/content/bulk-delete';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '<?= $this->escape($csrf_token) ?>';
            form.appendChild(csrfInput);
            
            // Add content IDs
            contentIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'content_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    // Handle bulk publish/mark as ready
    bulkPublishBtn.addEventListener('click', function() {
        const selectedItems = Array.from(document.querySelectorAll('.item-checkbox:checked'));
        const count = selectedItems.length;
        
        if (count === 0) return;
        
        if (confirm(`Mark ${count} draft(s) as ready to publish? You can review them individually before publishing.`)) {
            const contentIds = selectedItems.map(item => item.value);
            
            // For now, just redirect to edit the first selected item
            // In a full implementation, you might want to mark them with a special status
            if (contentIds.length > 0) {
                window.location.href = `/admin/content/${contentIds[0]}/edit?bulk_ready=true`;
            }
        }
    });
    
    // Initial update
    updateSelectionUI();
});
</script>