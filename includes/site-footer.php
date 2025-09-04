<?php
/**
 * Site Footer Template
 * Contains the common footer HTML and JavaScript includes
 */

// Set default values if not set
if (!isset($site_title)) {
    $site_title = 'Dalthaus Photography';
}
?>
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>. All rights reserved.</p>
        <p>
            <a href="/articles">All Articles</a> | 
            <a href="/photobooks">All Photobooks</a>
        </p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger = document.getElementById('hamburger-menu');
        const mobileNav = document.getElementById('mobile-nav');
        const overlay = document.getElementById('nav-overlay');
        
        function toggleMenu() {
            hamburger.classList.toggle('active');
            mobileNav.classList.toggle('active');
            overlay.classList.toggle('active');
        }
        
        hamburger.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    });
    </script>

    <?php if (file_exists('assets/js/main.js')): ?>
        <script src="/assets/js/main.js"></script>
    <?php endif; ?>
</body>
</html>