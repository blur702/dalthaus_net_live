<?php
// Get site title for footer
$footer_site_title = 'Dalthaus Photography';
if (function_exists('getSetting')) {
    $footer_site_title = getSetting('site_title', 'Dalthaus Photography');
}

// Get menu items for the bottom menu
$bottom_menu_items = [];
if (function_exists('getMenuItems')) {
    $bottom_menu_items = getMenuItems('bottom');
}
?>
<footer class="site-footer">
    <div class="bottom-menu">
        <?php if (!empty($bottom_menu_items)): ?>
        <ul>
            <?php foreach ($bottom_menu_items as $item): ?>
            <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="footer-info">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($footer_site_title) ?>. All rights reserved.
    </div>
</footer>

<script src="/assets/js/main.js"></script>