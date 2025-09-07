<!-- Article Display Page -->
<div class="max-w-4xl mx-auto">
    <!-- Article Header -->
    <header class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2" style="font-family: Arial, sans-serif;">
            <?= $this->escape($article->getAttribute('title')) ?> (Article Display)
        </h1>
        
        <div class="text-sm text-gray-900 mb-4">
            <?= $this->escape($author['username'] ?? 'author') ?> / <?= $article->getFormattedPublishedDate() ?>
        </div>
        
        <?php if ($article->getAttribute('teaser_image')): ?>
        <div class="mb-6">
            <div class="text-xs text-gray-600 mb-2">(Featured image here at 4:3 aspect ratio)</div>
            <img src="<?= $this->escape($article->getTeaserImageUrl()) ?>" 
                 alt="<?= $this->escape($article->getAttribute('title')) ?>"
                 class="teaser-image mx-auto max-w-md">
        </div>
        <?php endif; ?>
    </header>
    
    <!-- Article Content -->
    <article class="prose max-w-none">
        <?php if ($total_pages > 1): ?>
        <h2 class="text-lg font-bold mb-4" style="font-family: Arial, sans-serif;">
            Article Section title
        </h2>
        <?php endif; ?>
        
        <div class="content-text leading-relaxed text-gray-900">
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
    <!-- Article Navigation -->
    <div class="mt-8 mb-8 text-center border-t border-b border-gray-300 py-4">
        <div class="text-sm text-gray-900 mb-2">Pages in this Article</div>
        <div class="text-xs text-gray-600">Drop Down: Section titles</div>
    </div>
    
    <!-- Page Navigation -->
    <div class="pagination">
        <!-- Previous Page -->
        <?php if ($current_page > 1): ?>
        <a href="<?= $this->escape($article->getUrl() . '?p=' . ($current_page - 1)) ?>" aria-label="Previous page">&lt;</a>
        <?php else: ?>
        <span class="disabled">&lt;</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= $this->escape($article->getUrl() . '?p=' . $i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($total_pages > 8): ?>
        <span>...</span>
        <?php endif; ?>
        
        <!-- Next Page -->
        <?php if ($current_page < $total_pages): ?>
        <a href="<?= $this->escape($article->getUrl() . '?p=' . ($current_page + 1)) ?>" aria-label="Next page">&gt;</a>
        <?php else: ?>
        <span class="disabled">&gt;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
