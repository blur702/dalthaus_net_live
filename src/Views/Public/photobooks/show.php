<!-- Photobook Display Page -->
<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
    
    <?php if (isset($debug_message)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <strong>Debug:</strong> <?= $this->escape($debug_message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Photobook Header -->
    <header class="mb-4 sm:mb-6 lg:mb-8 text-center">
        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 mb-2 leading-tight px-2 sm:px-0" style="font-family: 'Arimo', Arial, sans-serif;">
            <?= $this->escape($photobook ? $photobook->getAttribute('title') : $page_title) ?>
        </h1>

        <div class="text-xs sm:text-sm text-gray-900 mb-3 sm:mb-4">
            <?= $this->escape($author['display_name'] ?? $author['username'] ?? 'author') ?> / 
            <?= $photobook ? $photobook->getFormattedPublishedDate() : 'Date unavailable' ?>
        </div>

        <?php if ($can_edit && $photobook): ?>
        <div class="mb-3 sm:mb-4">
            <a href="/admin/content/<?= $photobook->getAttribute('content_id') ?>/edit" 
               class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 hover:text-blue-700 transition-colors">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit Photobook
            </a>
        </div>
        <?php endif; ?>
    </header>
    
    
    <!-- Photobook Content -->
    <article class="prose max-w-none">
        
        <div class="content-text leading-relaxed text-gray-900 px-1 sm:px-0">
            <?php if (!empty($content)): ?>
                <?= $content ?>
            <?php else: ?>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla failisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
            <?php endif; ?>
        </div>
    </article>

    <?php if ($total_pages > 1 && $photobook): ?>

    <!-- Page Navigation -->
    <div class="pagination">
        <!-- Previous Page -->
        <?php if ($current_page > 1): ?>
        <a href="<?= $this->escape($photobook->getUrl() . '?p=' . ($current_page - 1)) ?>" aria-label="Previous page">&lt;</a>
        <?php else: ?>
        <span class="disabled">&lt;</span>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php
        // Calculate the range of pages to display (2 before, current, 2 after)
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <?php if ($i == $current_page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= $this->escape($photobook->getUrl() . '?p=' . $i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Next Page -->
        <?php if ($current_page < $total_pages): ?>
        <a href="<?= $this->escape($photobook->getUrl() . '?p=' . ($current_page + 1)) ?>" aria-label="Next page">&gt;</a>
        <?php else: ?>
        <span class="disabled">&gt;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
