<?php
/**
 * Unified Header Component
 * Displays site title and motto from settings or defaults
 */

// Initialize default values first
$site_title = 'Dalthaus Photography';
$site_motto = 'Capturing moments, telling stories through light and shadow';

// Try to get settings if functions exist
if (function_exists('getSetting')) {
    $site_title = getSetting('site_title', 'Dalthaus Photography');
    $site_motto = getSetting('site_motto', 'Capturing moments, telling stories through light and shadow');
    $header_image = getSetting('header_image', '');
    $header_height = getSetting('header_height', '200');
    $header_overlay_color = getSetting('header_overlay_color', 'rgba(0,0,0,0.3)');
    $header_text_color = getSetting('header_text_color', '#ffffff');
} else {
    // Fallback if functions.php not loaded
    $header_image = '';
    $header_height = '200';
    $header_overlay_color = 'rgba(0,0,0,0.3)';
    $header_text_color = '#ffffff';
}

// Ensure we have values
if (empty($site_title)) $site_title = 'Dalthaus Photography';
if (empty($site_motto)) $site_motto = 'Capturing moments, telling stories through light and shadow';

// Custom styling for the header
$header_style = '';
if (!empty($header_image)) {
    $header_style = "background-image: url('" . htmlspecialchars($header_image) . "'); background-size: cover; background-position: center; min-height: " . htmlspecialchars($header_height) . "px;";
}

$header_text_style = "color: " . htmlspecialchars($header_text_color) . ";";

?>
<!-- Hamburger Menu (fixed position) -->
<button class="hamburger-menu" id="hamburgerMenu" aria-label="Open menu" aria-controls="mobileNav">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Header -->
<header class="site-header" <?php if ($header_style) echo 'style="' . $header_style . '"'; ?>>
    <?php if (!empty($header_image)): ?>
    <div class="header-overlay" style="background-color: <?php echo htmlspecialchars($header_overlay_color); ?>"></div>
    <?php endif; ?>
    <div class="header-content">
        <div class="header-text">
            <h1 class="site-title" style="<?php echo $header_text_style; ?>">
                <a href="/"><?php echo htmlspecialchars($site_title); ?></a>
            </h1>
            <p class="site-motto" style="<?php echo $header_text_style; ?>"><?php echo htmlspecialchars($site_motto); ?></p>
        </div>
    </div>
</header>

<!-- Mobile Navigation -->
<nav class="slide-menu" id="mobileNav">
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/articles">Articles</a></li>
        <li><a href="/photobooks">Photobooks</a></li>
        <li><a href="/about">About</a></li>
        <li><a href="/contact">Contact</a></li>
    </ul>
</nav>

<!-- Navigation Overlay -->
<div class="nav-overlay" id="navOverlay"></div>