<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check maintenance mode
checkMaintenanceMode();

// Get the slug from URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    showError(404);
}

try {
    // Get article from database
    $article = getArticleBySlug($slug);
    if (!$article) {
        showError(404);
    }
    
    // Set page title
    $pageTitle = htmlspecialchars($article['title']);
    
    // Include header
    include __DIR__ . '/../includes/header.php';
    
} catch (Exception $e) {
    error_log("Article page error: " . $e->getMessage());
    showError(500);
}
?>

<div class="container">
    <article class="content-item">
        <div class="content-image">
            <?php if (!empty($article['image_filename'])): ?>
                <img src="/uploads/<?php echo htmlspecialchars($article['image_filename']); ?>" 
                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                     style="aspect-ratio: 4/3; object-fit: cover; width: 100%;">
            <?php endif; ?>
        </div>
        <div class="content-text">
            <h1><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="content-body">
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>
            <div class="content-meta">
                <p class="publish-date">Published: <?php echo date('F j, Y', strtotime($article['created_at'])); ?></p>
            </div>
        </div>
    </article>
</div>

<?php
// Include footer if it exists
if (file_exists(__DIR__ . '/../includes/footer.php')) {
    include __DIR__ . '/../includes/footer.php';
}
?>