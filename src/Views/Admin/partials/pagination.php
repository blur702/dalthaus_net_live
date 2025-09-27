<?php
/**
 * Admin Pagination Component
 *
 * Styled for admin interface with Tailwind CSS
 *
 * Required variables:
 * - $pagination: Array with current_page, total_pages, total_items, per_page
 * - $baseUrl: Base URL for pagination links
 */

$current_page = $pagination['current_page'] ?? 1;
$total_pages = $pagination['total_pages'] ?? 1;
$total_items = $pagination['total_items'] ?? 0;
$per_page = $pagination['per_page'] ?? 10;
?>

<?php if ($total_pages > 1): ?>
<div class="flex items-center justify-between">
    <div class="flex-1 flex justify-between sm:hidden">
        <!-- Mobile pagination -->
        <?php if ($current_page > 1): ?>
        <a href="<?= $this->escape($baseUrl . '&page=' . ($current_page - 1)) ?>"
           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Previous
        </a>
        <?php else: ?>
        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
            Previous
        </span>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
        <a href="<?= $this->escape($baseUrl . '&page=' . ($current_page + 1)) ?>"
           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
            Next
        </a>
        <?php else: ?>
        <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
            Next
        </span>
        <?php endif; ?>
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium"><?= ($current_page - 1) * $per_page + 1 ?></span>
                to
                <span class="font-medium"><?= min($current_page * $per_page, $total_items) ?></span>
                of
                <span class="font-medium"><?= $total_items ?></span>
                results
            </p>
        </div>

        <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Previous button -->
                <?php if ($current_page > 1): ?>
                <a href="<?= $this->escape($baseUrl . '&page=' . ($current_page - 1)) ?>"
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Previous</span>
                    <!-- Previous icon -->
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
                <?php endif; ?>

                <!-- Page numbers -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);

                // Always show first page if not in range
                if ($start_page > 1): ?>
                    <a href="<?= $this->escape($baseUrl . '&page=1') ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        1
                    </a>
                    <?php if ($start_page > 2): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                        ...
                    </span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                    <span aria-current="page" class="z-10 bg-blue-50 border-blue-500 text-blue-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?= $i ?>
                    </span>
                    <?php else: ?>
                    <a href="<?= $this->escape($baseUrl . '&page=' . $i) ?>"
                       class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?= $i ?>
                    </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Always show last page if not in range -->
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                        ...
                    </span>
                    <?php endif; ?>
                    <a href="<?= $this->escape($baseUrl . '&page=' . $total_pages) ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <?= $total_pages ?>
                    </a>
                <?php endif; ?>

                <!-- Next button -->
                <?php if ($current_page < $total_pages): ?>
                <a href="<?= $this->escape($baseUrl . '&page=' . ($current_page + 1)) ?>"
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <span class="sr-only">Next</span>
                    <!-- Next icon -->
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
                <?php else: ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>
<?php endif; ?>