<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
checkMaintenanceMode();

$pdo = Database::getInstance();

// Get the page slug from URL
$slug = trim($_SERVER['REQUEST_URI'], '/');

// Get page from database
$stmt = $pdo->prepare("
    SELECT * FROM content 
    WHERE slug = ? 
    AND type = 'page' 
    AND status = 'published' 
    AND deleted_at IS NULL
");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    showError(404);
}

// Set page title
$pageTitle = $page['title'];

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
    
    <main id="main" class="site-main">
        <div class="container">
            <article class="page-wrapper">
                <header class="page-header">
                    <h1><?= htmlspecialchars($page['title']) ?></h1>
                </header>
                
                <div class="page-body">
                    <?= renderContent($page['body']) ?>
                </div>
            </article>
        </div>
    </main>
    
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';