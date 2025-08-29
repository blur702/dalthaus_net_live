<?php
/**
 * Reusable footer template for all front-end pages
 * Includes footer menu, copyright, and common scripts
 */

// Get bottom menu items if not already loaded
if (!isset($bottomMenu)) {
    $bottomMenu = $pdo->query("
        SELECT m.*, c.title, c.slug, c.type 
        FROM menus m
        JOIN content c ON m.content_id = c.id
        WHERE m.location = 'bottom' 
        AND m.is_active = TRUE
        AND c.deleted_at IS NULL
        ORDER BY m.sort_order
    ")->fetchAll();
}
?>
        <!-- Footer with bottom menu -->
        <footer class="site-footer">
            <?php if ($bottomMenu && count($bottomMenu) > 0): ?>
            <nav class="bottom-menu" role="navigation" aria-label="Footer navigation">
                <ul>
                    <?php foreach ($bottomMenu as $item): 
                        // Build URL based on content type and special cases
                        if ($item['type'] === 'page') {
                            // Special handling for system pages
                            if ($item['slug'] === 'home') {
                                $url = '/';
                            } elseif ($item['slug'] === 'articles-listing') {
                                $url = '/articles';
                            } elseif ($item['slug'] === 'photobooks-listing') {
                                $url = '/photobooks';
                            } else {
                                $url = '/' . $item['slug'];
                            }
                        } elseif ($item['type'] === 'article') {
                            $url = '/article/' . $item['slug'];
                        } elseif ($item['type'] === 'photobook') {
                            $url = '/photobook/' . $item['slug'];
                        } else {
                            $url = '/' . $item['slug'];
                        }
                    ?>
                    <li><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="footer-info">
                <p><?= $settings['copyright_notice'] ?? '© ' . date('Y') . ' Dalthaus.net. All rights reserved.' ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Hamburger menu script -->
    <script>
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            const menu = document.getElementById('slide-menu');
            const hamburger = this;
            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('slide-menu');
            const hamburger = document.getElementById('hamburger-menu');
            if (!menu.contains(event.target) && !hamburger.contains(event.target)) {
                menu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    </script>
    
    <?php if (isset($additionalScripts)): ?>
    <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>