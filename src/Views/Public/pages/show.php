<!-- Static Page Display -->
<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <header class="mb-4 sm:mb-6 lg:mb-8 text-center">
        <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-900 mb-3 sm:mb-4 leading-tight px-2 sm:px-0" style="font-family: 'Arimo', Arial, sans-serif;">
            <?= $this->escape($page->getAttribute('title')) ?>
        </h1>
    </header>
    
    <!-- View Mode Toggle -->
    <?php if ($total_pages > 1): ?>
    <div class="mb-3 sm:mb-4 text-center px-2 sm:px-0">
        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 w-full sm:w-auto max-w-sm">
            <a href="<?= $this->escape($page->getUrl()) ?>"
               class="<?= (!isset($view_mode) || $view_mode === 'paginated') ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 sm:px-4 py-2 rounded-md text-xs sm:text-sm font-medium transition-colors flex-1 sm:flex-none text-center">
                Paginated View
            </a>
            <a href="<?= $this->escape($page->getUrl() . '?view=full') ?>"
               class="<?= (isset($view_mode) && $view_mode === 'full') ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-100' ?> px-3 sm:px-4 py-2 rounded-md text-xs sm:text-sm font-medium transition-colors flex-1 sm:flex-none text-center">
                Full View
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Page Content -->
    <article class="prose max-w-none">
        
        <div class="content-text leading-relaxed text-gray-900 px-1 sm:px-0">
            <?php if (!empty($content)): ?>
                <?= $content ?>
            <?php elseif ($page->getAttribute('body')): ?>
                <?= $page->getContentWithPageBreaks() ?>
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
                    This is a static page that can contain any content. It follows the same clean, simple design as the rest of the site with consistent typography and spacing.
                </p>
            <?php endif; ?>
        </div>
    </article>
    
    <?php if ($total_pages > 1 && (!isset($view_mode) || $view_mode === 'paginated')): ?>
    <!-- Page Navigation -->
    <div class="mt-6 sm:mt-8 mb-6 sm:mb-8 text-center border-t border-b border-gray-300 py-3 sm:py-4 mx-1 sm:mx-0">
        <div class="text-xs sm:text-sm text-gray-900 mb-1 sm:mb-2">Pages in this Content</div>
        <div class="text-xs text-gray-600">Drop Down: Section titles</div>
    </div>
    
    <!-- Page Navigation -->
    <div class="pagination">
        <!-- Previous Page -->
        <?php if ($current_page > 1): ?>
        <a href="<?= $this->escape($page->getUrl() . '?p=' . ($current_page - 1)) ?>" aria-label="Previous page">&lt;</a>
        <?php else: ?>
        <span class="disabled">&lt;</span>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $current_page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= $this->escape($page->getUrl() . '?p=' . $i) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($total_pages > 8): ?>
        <span>...</span>
        <?php endif; ?>
        
        <!-- Next Page -->
        <?php if ($current_page < $total_pages): ?>
        <a href="<?= $this->escape($page->getUrl() . '?p=' . ($current_page + 1)) ?>" aria-label="Next page">&gt;</a>
        <?php else: ?>
        <span class="disabled">&gt;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
