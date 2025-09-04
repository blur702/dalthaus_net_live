<?php
/**
 * Navigation Template
 * Contains both hamburger menu and mobile navigation
 */

// Set default values if not set
if (!isset($site_title)) {
    $site_title = 'Dalthaus Photography';
}
if (!isset($page_title)) {
    $page_title = '';
}
?>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu" id="hamburger-menu">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobile-nav">
        <a href="/">Home</a>
        <a href="/articles">All Articles</a>
        <a href="/photobooks">All Photobooks</a>
        <a href="/admin/login.php">Admin</a>
    </div>

    <!-- Overlay -->
    <div class="nav-overlay" id="nav-overlay"></div>

    <!-- Header -->
    <header class="header">
        <a href="/" class="site-title"><?php echo htmlspecialchars($site_title); ?></a>
        <?php if (!empty($page_title)): ?>
            <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
        <?php endif; ?>
    </header>