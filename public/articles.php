<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Articles - ' . getSetting('site_title', 'Dalthaus Photography');

// Get all published articles
$articles = getAllContent('article');

// Start output buffering to capture the page content
ob_start();
?>

<div class="container">
    <h1 class="page-title">Articles</h1>
    <div class="listings-grid">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $article): ?>
            <article class="listing-card">
                <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="listing-card-image">
                    <?php if (!empty($article['featured_image'])): ?>
                        <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="listing-thumbnail">
                    <?php else: ?>
                        <div class="image-placeholder"></div>
                    <?php endif; ?>
                </a>
                <div class="listing-card-content">
                    <h2 class="listing-title">
                        <a href="/article/<?= htmlspecialchars($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
                    </h2>
                    <div class="listing-meta">
                        By <?= htmlspecialchars($article['author']) ?> on <?= date('F j, Y', strtotime($article['created_at'])) ?>
                    </div>
                    <div class="listing-excerpt">
                        <?= htmlspecialchars(substr(strip_tags($article['content']), 0, 200)) ?>...
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-content">No articles have been published yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Get the captured content and assign it to a variable
$page_content = ob_get_clean();

// Include the template file
require_once __DIR__ . '/template.php';
?>