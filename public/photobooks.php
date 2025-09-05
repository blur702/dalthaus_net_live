<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Photobooks - ' . getSetting('site_title', 'Dalthaus Photography');

// Get all published photobooks
$photobooks = getAllContent('photobook');

// Start output buffering to capture the page content
ob_start();
?>

<div class="container">
    <h1 class="page-title">Photobooks</h1>
    <div class="photobooks-grid">
        <?php if (!empty($photobooks)): ?>
            <?php foreach ($photobooks as $photobook): ?>
            <article class="photobook-card">
                <a href="/photobook/<?= htmlspecialchars($photobook['slug']) ?>" class="card-image-link">
                    <?php if (!empty($photobook['cover_image'])): ?>
                        <img src="<?= htmlspecialchars($photobook['cover_image']) ?>" alt="<?= htmlspecialchars($photobook['title']) ?>" class="card-image">
                    <?php else: ?>
                        <div class="image-placeholder card-image"></div>
                    <?php endif; ?>
                </a>
                <div class="card-content">
                    <h2 class="card-title">
                        <a href="/photobook/<?= htmlspecialchars($photobook['slug']) ?>"><?= htmlspecialchars($photobook['title']) ?></a>
                    </h2>
                    <div class="card-meta">
                        By <?= htmlspecialchars($photobook['author']) ?> on <?= date('F j, Y', strtotime($photobook['created_at'])) ?>
                    </div>
                    <div class="card-excerpt">
                        <?= htmlspecialchars(substr(strip_tags($photobook['description']), 0, 150)) ?>...
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-content">No photobooks have been published yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Get the captured content and assign it to a variable
$page_content = ob_get_clean();

// Include the template file
require_once __DIR__ . '/template.php';
?>