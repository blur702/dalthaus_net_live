<?php
/**
 * Pagination Component
 * 
 * Usage:
 * include 'pagination.php';
 * 
 * Required variables:
 * - $current_page: Current page number
 * - $total_pages: Total number of pages
 * - $base_url: Base URL for pagination links
 */
?>

<?php if ($total_pages > 1): ?>
<div class="pagination">
    <!-- Previous Page -->
    <?php if ($current_page > 1): ?>
    <a href="<?= $this->escape($base_url . '?page=' . ($current_page - 1)) ?>" aria-label="Previous page">&lt;</a>
    <?php else: ?>
    <span class="disabled">&lt;</span>
    <?php endif; ?>
    
    <!-- Page Numbers -->
    <?php 
    $start_page = max(1, $current_page - 3);
    $end_page = min($total_pages, $current_page + 3);
    
    // Always show first page if not in range
    if ($start_page > 1): ?>
        <a href="<?= $this->escape($base_url . '?page=1') ?>">1</a>
        <?php if ($start_page > 2): ?>
        <span>...</span>
        <?php endif; ?>
    <?php endif; ?>
    
    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
        <?php if ($i == $current_page): ?>
        <span class="current"><?= $i ?></span>
        <?php else: ?>
        <a href="<?= $this->escape($base_url . '?page=' . $i) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <!-- Always show last page if not in range -->
    <?php if ($end_page < $total_pages): ?>
        <?php if ($end_page < $total_pages - 1): ?>
        <span>...</span>
        <?php endif; ?>
        <a href="<?= $this->escape($base_url . '?page=' . $total_pages) ?>"><?= $total_pages ?></a>
    <?php endif; ?>
    
    <!-- Next Page -->
    <?php if ($current_page < $total_pages): ?>
    <a href="<?= $this->escape($base_url . '?page=' . ($current_page + 1)) ?>" aria-label="Next page">&gt;</a>
    <?php else: ?>
    <span class="disabled">&gt;</span>
    <?php endif; ?>
</div>
<?php endif; ?>
