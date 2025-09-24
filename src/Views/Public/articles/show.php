<!-- Article Display Page -->
<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
    <!-- Article Header -->
    <header class="mb-4 sm:mb-6 lg:mb-8 text-center">
        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 mb-2 leading-tight px-2 sm:px-0" style="font-family: 'Arimo', Arial, sans-serif;">
            <?= $this->escape($article->getAttribute('title')) ?>
        </h1>

        <div class="text-xs sm:text-sm text-gray-900 mb-3 sm:mb-4">
            <?= $this->escape($author['display_name'] ?? $author['username'] ?? 'author') ?> / <?= $article->getFormattedPublishedDate() ?>
        </div>
    </header>

    <!-- Article Content -->
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
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
            <?php endif; ?>
        </div>
    </article>
    
    <?php if ($total_pages > 1): ?>
    
    <!-- Page Navigation -->
    <div class="pagination">
        <!-- Previous Page -->
        <?php if ($current_page > 1): ?>
        <a href="<?= $this->escape($article->getUrl() . '?p=' . ($current_page - 1)) ?>" aria-label="Previous page">&lt;</a>
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
            <a href="<?= $this->escape($article->getUrl() . '?p=' . $i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <!-- Next Page -->
        <?php if ($current_page < $total_pages): ?>
        <a href="<?= $this->escape($article->getUrl() . '?p=' . ($current_page + 1)) ?>" aria-label="Next page">&gt;</a>
        <?php else: ?>
        <span class="disabled">&gt;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
