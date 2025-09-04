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
    // Get photobook from database
    $photobook = getPhotobookBySlug($slug);
    if (!$photobook) {
        showError(404);
    }
    
    // Set page title
    $pageTitle = htmlspecialchars($photobook['title']);
    
    // Include header
    include __DIR__ . '/../includes/header.php';
    
} catch (Exception $e) {
    error_log("Photobook page error: " . $e->getMessage());
    showError(500);
}
?>

<div class="container">
    <article class="content-item">
        <div class="content-image">
            <?php if (!empty($photobook['image_filename'])): ?>
                <img src="/uploads/<?php echo htmlspecialchars($photobook['image_filename']); ?>" 
                     alt="<?php echo htmlspecialchars($photobook['title']); ?>" 
                     style="aspect-ratio: 4/3; object-fit: cover; width: 100%;">
            <?php endif; ?>
        </div>
        <div class="content-text">
            <h1><?php echo htmlspecialchars($photobook['title']); ?></h1>
            <div class="content-body">
                <?php echo nl2br(htmlspecialchars($photobook['content'])); ?>
            </div>
            <div class="content-meta">
                <p class="publish-date">Published: <?php echo date('F j, Y', strtotime($photobook['created_at'])); ?></p>
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