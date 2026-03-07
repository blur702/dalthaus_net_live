<?php
/**
 * Blog Listing - Public View
 * Displays all published blog posts with pagination and filtering
 */
?>

<!-- Hero Section -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">Blog</h1>
            <p class="text-xl opacity-90">Thoughts, insights, and updates</p>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<?php if (!empty($posts) || !empty($search) || !empty($selectedTags)): ?>
<div class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search Input -->
            <div class="flex-1 min-w-0">
                <input type="text" name="search" value="<?= $this->escape($search ?? '') ?>"
                       placeholder="Search blog posts..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <!-- Tag Filter -->
            <?php if (!empty($availableTags)): ?>
            <div class="flex items-center space-x-2">
                <label for="tags" class="text-sm font-medium text-gray-700">Tags:</label>
                <select name="tags[]" multiple class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <?php foreach ($availableTags as $tag): ?>
                    <option value="<?= $this->escape($tag) ?>" <?= in_array($tag, $selectedTags ?? []) ? 'selected' : '' ?>>
                        <?= $this->escape($tag) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Search
            </button>
            
            <?php if (!empty($search) || !empty($selectedTags)): ?>
            <a href="/blog" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <?php if (!empty($posts)): ?>
    
    <!-- Active Filters Display -->
    <?php if (!empty($search) || !empty($selectedTags)): ?>
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <span>Active filters:</span>
            <?php if (!empty($search)): ?>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Search: "<?= $this->escape($search) ?>"
            </span>
            <?php endif; ?>
            <?php if (!empty($selectedTags)): ?>
                <?php foreach ($selectedTags as $tag): ?>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Tag: <?= $this->escape($tag) ?>
                </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Blog Posts Grid -->
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($posts as $post): ?>
        <article class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-300 ease-in-out overflow-hidden">
            <!-- Featured Image -->
            <?php if (!empty($post['featured_image'])): ?>
            <div class="aspect-w-16 aspect-h-9">
                <img src="<?= $this->escape($post['featured_image']) ?>" 
                     alt="<?= $this->escape($post['title']) ?>"
                     class="w-full h-48 object-cover">
            </div>
            <?php endif; ?>
            
            <!-- Post Content -->
            <div class="p-6">
                <!-- Tags -->
                <?php if (!empty($post['tags'])): ?>
                <div class="mb-3">
                    <?php 
                    $tags = is_string($post['tags']) ? array_filter(array_map('trim', explode(',', $post['tags']))) : [];
                    foreach ($tags as $tag): 
                    ?>
                    <a href="/blog?tags[]=<?= urlencode($tag) ?>" 
                       class="inline-block bg-gray-100 hover:bg-gray-200 rounded-full px-3 py-1 text-sm text-gray-700 mr-2 mb-1 transition duration-150">
                        #<?= $this->escape($tag) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Title -->
                <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                    <a href="/blog/<?= $this->escape($post['url_alias']) ?>" class="hover:text-blue-600 transition duration-150">
                        <?= $this->escape($post['title']) ?>
                    </a>
                </h2>
                
                <!-- Excerpt -->
                <?php if (!empty($post['excerpt'])): ?>
                <p class="text-gray-600 mb-4 line-clamp-3">
                    <?= $this->escape($post['excerpt']) ?>
                </p>
                <?php endif; ?>
                
                <!-- Meta Information -->
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                        </svg>
                        <?= date('M j, Y', strtotime($post['published_at'])) ?>
                    </div>
                    
                    <?php if (!empty($post['reading_time'])): ?>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                        <?= $this->escape($post['reading_time']) ?> min
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Read More Link -->
                <div class="mt-4">
                    <a href="/blog/<?= $this->escape($post['url_alias']) ?>" 
                       class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition duration-150">
                        Read more
                        <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="mt-12 flex items-center justify-between">
        <div class="text-sm text-gray-700">
            Showing <?= ($pagination['current_page'] - 1) * $pagination['items_per_page'] + 1 ?> to
            <?= min($pagination['current_page'] * $pagination['items_per_page'], $pagination['total_items']) ?> of
            <?= $pagination['total_items'] ?> posts
        </div>
        
        <nav class="flex space-x-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])) ?>"
               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition duration-150">
                Previous
            </a>
            <?php endif; ?>
            
            <span class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md">
                Page <?= $pagination['current_page'] ?> of <?= $pagination['total_pages'] ?>
            </span>
            
            <?php if ($pagination['has_next']): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])) ?>"
               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition duration-150">
                Next
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-16">
        <div class="mx-auto h-24 w-24 text-gray-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 48 48">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                No posts found
            <?php else: ?>
                No blog posts yet
            <?php endif; ?>
        </h3>
        <p class="mt-2 text-gray-500">
            <?php if (!empty($search) || !empty($selectedTags)): ?>
                Try adjusting your search criteria or <a href="/blog" class="text-blue-600 hover:text-blue-800">browse all posts</a>.
            <?php else: ?>
                Check back soon for new content.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
/* Custom styles for line clamping */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.aspect-w-16 {
    position: relative;
    padding-bottom: calc(9 / 16 * 100%);
}

.aspect-w-16 > * {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}
</style>