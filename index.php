<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/router.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle routing
$route = $_GET['route'] ?? '';

// If there's no route or route is empty, show homepage
if ($route === '' || $route === '/') {
    // Continue to homepage display below
    $show_homepage = true;
} else {
    // Parse the route
    $routeParts = explode('/', trim($route, '/'));
    $contentType = $routeParts[0] ?? '';
    $slug = $routeParts[1] ?? '';
    
    // Route to appropriate handler
    if ($contentType === 'article' && $slug) {
        $_GET['params'] = [$slug];
        require_once __DIR__ . '/public/article.php';
        exit;
    } elseif ($contentType === 'photobook' && $slug) {
        $_GET['params'] = [$slug];
        require_once __DIR__ . '/public/photobook.php';
        exit;
    } elseif ($contentType === 'page' && $slug) {
        $_GET['params'] = [$slug];
        require_once __DIR__ . '/public/page.php';
        exit;
    } elseif ($contentType === 'articles') {
        require_once __DIR__ . '/public/articles.php';
        exit;
    } elseif ($contentType === 'photobooks') {
        require_once __DIR__ . '/public/photobooks.php';
        exit;
    } else {
        // Unknown route
        showError(404);
        exit;
    }
}

// Homepage display - if we reach here, show the homepage
if (isset($show_homepage)) {
    try {
        $pdo = Database::getInstance();
        $db_connected = true;
    } catch (Exception $e) {
        logMessage('Database connection failed: ' . $e->getMessage(), 'error');
        $db_connected = false;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dalthaus Photography</title>
    <style>
        body { 
            font-family: 'Gelasio', Georgia, serif; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px;
            background: #f8f8f8;
        }
        h1 { 
            font-family: 'Arimo', Arial, sans-serif;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin: 30px 0;
        }
        .content-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .content-item h2 {
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .content-item p {
            color: #7f8c8d;
            line-height: 1.6;
        }
        .nav {
            background: #2c3e50;
            padding: 15px;
            margin: -20px -20px 20px -20px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .status {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            color: #155724;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="nav">
    <a href="/">Home</a>
    <a href="/admin/login.php">Admin</a>
    <a href="/articles">Articles</a>
    <a href="/photobooks">Photobooks</a>
</div>

<h1>Dalthaus Photography</h1>

<?php if ($db_connected): ?>
    <div class="status">
        ✅ Database connected successfully! The site is operational.
    </div>
    
    <div class="content-grid">
    <?php
    try {
        // Get published content
        $stmt = $pdo->prepare("SELECT * FROM content WHERE status = 'published' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 6");
        $stmt->execute();
        $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($content && count($content) > 0) {
            foreach ($content as $row) {
                echo '<div class="content-item">';
                echo '<h2>' . htmlspecialchars($row['title']) . '</h2>';
                echo '<p>' . htmlspecialchars(substr(strip_tags($row['body']), 0, 150)) . '...</p>';
                echo '<p><a href="/' . $row['type'] . '/' . $row['slug'] . '">Read more →</a></p>';
                echo '</div>';
            }
        } else {
            echo '<div class="content-item">';
            echo '<h2>Welcome</h2>';
            echo '<p>No content published yet. <a href="/admin/login.php">Login to admin</a> to add content.</p>';
            echo '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="status error">';
        echo 'Error loading content: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    ?>
    </div>
    
<?php else: ?>
    <div class="status error">
        ❌ Database connection failed. Please check configuration.
    </div>
<?php endif; ?>

</body>
</html>