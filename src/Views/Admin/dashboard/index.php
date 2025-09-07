<!-- Welcome Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= $this->escape($greeting ?? 'Welcome') ?></h1>
            <p class="text-gray-600">Welcome to your CMS admin panel</p>
            <?php if (!empty($current_user)): ?>
                <p class="text-sm text-gray-500 mt-1">
                    Last login: <?= $this->formatDate($current_user['created_at'] ?? null, 'M j, Y g:i A') ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="text-right text-sm text-gray-500">
            <div><?= date('l, F j, Y') ?></div>
            <div class="font-medium"><?= date('g:i A') ?></div>
        </div>
    </div>
</div>

<!-- System Health Widget -->
<div class="mb-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">System Health</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <?php foreach ($system_health as $component => $health): ?>
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-3 h-3 rounded-full <?= $health['status'] === 'healthy' ? 'bg-green-400' : ($health['status'] === 'warning' ? 'bg-yellow-400' : 'bg-red-400') ?>"></div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        <?= ucwords(str_replace('_', ' ', $component)) ?>
                    </p>
                    <p class="text-xs text-gray-500 truncate"><?= $this->escape($health['message']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Enhanced Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Today's Activity -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">T</span>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Today's Activity</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?= $stats['activities_today'] ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span class="text-indigo-600 font-medium"><?= $stats['content_today'] ?> content created</span>
            </div>
        </div>
    </div>

    <!-- Weekly Summary -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">W</span>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">This Week</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?= $stats['content_week'] ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span class="text-green-600 font-medium"><?= $stats['published_week'] ?> published</span>
            </div>
        </div>
    </div>

    <!-- Articles Stats -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">A</span>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Articles</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?= $stats['total_articles'] ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span class="text-green-600 font-medium"><?= $stats['published_articles'] ?> published</span>
                <span class="text-gray-600"> / </span>
                <span class="text-yellow-600 font-medium"><?= $stats['draft_articles'] ?> drafts</span>
            </div>
        </div>
    </div>

    <!-- Photobooks Stats -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">P</span>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Photobooks</dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900">
                                <?= $stats['total_photobooks'] ?>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <span class="text-green-600 font-medium"><?= $stats['published_photobooks'] ?> published</span>
                <span class="text-gray-600"> / </span>
                <span class="text-yellow-600 font-medium"><?= $stats['draft_photobooks'] ?> drafts</span>
            </div>
        </div>
    </div>
</div>

<!-- Content Trends Chart -->
<div class="mb-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Content Trends (Last 14 Days)</h3>
        <div class="h-64">
            <div class="flex items-end justify-between h-full space-x-2">
                <?php 
                $content_trends = $content_trends ?? ['articles' => [], 'photobooks' => [], 'dates' => []];
                $maxValue = max(array_merge($content_trends['articles'], $content_trends['photobooks'])) ?: 1;
                for ($i = 0; $i < count($content_trends['dates']); $i++): 
                    $articles = $content_trends['articles'][$i];
                    $photobooks = $content_trends['photobooks'][$i];
                    $articleHeight = ($articles / $maxValue) * 100;
                    $photobookHeight = ($photobooks / $maxValue) * 100;
                ?>
                <div class="flex flex-col items-center space-y-2 flex-1">
                    <div class="flex items-end space-x-1 h-48">
                        <div class="bg-blue-500 rounded-t" style="height: <?= $articleHeight ?>%; width: 12px;" title="<?= $articles ?> articles"></div>
                        <div class="bg-purple-500 rounded-t" style="height: <?= $photobookHeight ?>%; width: 12px;" title="<?= $photobooks ?> photobooks"></div>
                    </div>
                    <div class="text-xs text-gray-500 transform -rotate-45 origin-left">
                        <?= $content_trends['dates'][$i] ?>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-center space-x-6">
            <div class="flex items-center">
                <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                <span class="text-sm text-gray-600">Articles</span>
            </div>
            <div class="flex items-center">
                <div class="w-3 h-3 bg-purple-500 rounded mr-2"></div>
                <span class="text-sm text-gray-600">Photobooks</span>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Recent Activity -->
    <div class="lg:col-span-2 bg-white shadow rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
            <?php if (!empty($recent_activity)): ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-900">
                                <span class="font-medium"><?= $this->escape($activity['username'] ?? 'Unknown') ?></span>
                                <?= $this->escape($activity['action'] ?? 'performed action') ?>
                            </p>
                            <?php if (!empty($activity['description'])): ?>
                                <p class="text-sm text-gray-500"><?= $this->escape($activity['description']) ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">
                                <?= $this->formatTimeAgo(strtotime($activity['created_at'] ?? 'now')) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm italic">No recent activity.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions & Notifications -->
    <div class="space-y-6">
        <!-- Content Management Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Content Management</h3>
                <div class="space-y-3">
                    <a href="/admin/content" 
                       class="flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        View All Content
                    </a>
                    <a href="/admin/content/create" 
                       class="flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Article/Photobook
                    </a>
                    <a href="/admin/content-reorder" 
                       class="flex items-center px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                        Reorder Content
                    </a>
                </div>
                
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Page Management</h3>
                <div class="space-y-3">
                    <a href="/admin/pages" 
                       class="flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        View All Pages
                    </a>
                    <a href="/admin/pages/create" 
                       class="flex items-center px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Create New Page
                    </a>
                </div>
                
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Administration</h3>
                <div class="space-y-3">
                    <a href="/admin/users" 
                       class="flex items-center px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Manage Users
                    </a>
                    <a href="/admin/menus" 
                       class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        Manage Menus
                    </a>
                    <a href="/admin/settings" 
                       class="flex items-center px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Site Settings
                    </a>
                </div>
            </div>
        </div>

        <!-- Draft Reminders -->
        <?php if (!empty($draft_reminders)): ?>
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Draft Reminders</h3>
                <div class="space-y-3">
                    <?php foreach (array_slice($draft_reminders, 0, 5) as $draft): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?= $this->escape($draft['title']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= ucfirst($draft['content_type']) ?> • 
                                Updated <?= date('M j', strtotime($draft['updated_at'])) ?>
                            </p>
                        </div>
                        <div class="ml-2 flex-shrink-0">
                            <a href="/admin/content/<?= $draft['content_id'] ?>/edit" 
                               class="text-blue-600 hover:text-blue-500 text-sm">
                                Edit
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Recent Content</h3>
                <a href="/admin/content" class="text-sm text-blue-600 hover:text-blue-500">View all</a>
            </div>
            <?php if (!empty($recent_content)): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($recent_content, 0, 5) as $content): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?= $this->escape($content['title'] ?? '') ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= ucfirst($content['content_type'] ?? '') ?> • 
                                <?= ucfirst($content['status'] ?? '') ?> •
                                <?= date('M j', strtotime($content['updated_at'] ?? 'now')) ?>
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex space-x-2">
                            <a href="/<?= $content['content_type'] ?? 'content' ?>/<?= $content['url_alias'] ?? '' ?>" target="_blank" 
                               class="text-gray-400 hover:text-gray-600 text-sm" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="/admin/content/<?= $content['content_id'] ?? '' ?>/edit" 
                               class="text-blue-600 hover:text-blue-500 text-sm" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm italic">No content created yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Most Viewed Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Most Viewed Content</h3>
            <?php if (!empty($most_viewed)): ?>
                <div class="space-y-3">
                    <?php foreach ($most_viewed as $content): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                <?= $this->escape($content['title']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= ucfirst($content['content_type']) ?> •
                                Published <?= date('M j', strtotime($content['published_at'])) ?>
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <a href="/admin/content/<?= $content['content_id'] ?>/edit" 
                               class="text-blue-600 hover:text-blue-500 text-sm">
                                Edit
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm italic">No published content available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
