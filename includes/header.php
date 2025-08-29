<?php
/**
 * Reusable header template for all front-end pages
 * Includes site header, navigation menu, and common head elements
 */

// Ensure we have database connection
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
    $pdo = Database::getInstance();
}

// Get menu items if not already loaded
if (!isset($topMenu)) {
    $topMenu = $pdo->query("
        SELECT m.*, c.title, c.slug, c.type 
        FROM menus m
        JOIN content c ON m.content_id = c.id
        WHERE m.location = 'top' 
        AND m.is_active = TRUE
        AND c.deleted_at IS NULL
        ORDER BY m.sort_order
    ")->fetchAll();
}

// Get site settings if not already loaded
if (!isset($settings)) {
    $settings = [];
    $result = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default page title if not provided
if (!isset($pageTitle)) {
    $pageTitle = $settings['site_title'] ?? 'Dalthaus.net';
} else {
    $pageTitle = $pageTitle . ' - ' . ($settings['site_title'] ?? 'Dalthaus.net');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?= time() ?>">
    <?php if (isset($additionalStyles)): ?>
    <?= $additionalStyles ?>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header with site title and hamburger menu -->
        <header class="site-header" <?php if (!empty($settings['header_image'])): ?>
            style="background-image: linear-gradient(<?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>, <?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>), url('<?= htmlspecialchars($settings['header_image']) ?>'); background-size: cover; background-position: center; height: <?= $settings['header_height'] ?? '200' ?>px; color: <?= $settings['header_text_color'] ?? '#333333' ?>;"
        <?php else: ?>
            style="color: <?= $settings['header_text_color'] ?? '#333333' ?>;"
        <?php endif; ?>>
            <div class="header-content">
                <div class="header-text">
                    <h1 class="site-title">
                        <a href="/" style="color: inherit; text-decoration: none;">
                            <?= $settings['site_title'] ?? 'Dalthaus.net' ?>
                        </a>
                    </h1>
                    <?php if (!empty($settings['site_motto'])): ?>
                    <p class="site-motto" style="color: <?= $settings['header_text_color'] ?? '#333333' ?>;"><?= $settings['site_motto'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Hamburger menu button (always visible) -->
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        
        <!-- Slide-out navigation menu -->
        <nav class="slide-menu" id="slide-menu">
            <ul>
                <?php foreach ($topMenu as $item): 
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