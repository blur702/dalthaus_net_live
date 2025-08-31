<?php
// Direct article test - bypasses routing to test if articles work

// Direct database config
$db_host = 'localhost';
$db_name = 'dalthaus_photocms';
$db_user = 'dalthaus_photocms';
$db_pass = 'f-I*GSo^Urt*k*&#';

// Get slug from query parameter
$slug = $_GET['slug'] ?? 'welcome';

// Connect to database
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed");
}

// Get the article
$stmt = mysqli_prepare($conn, "SELECT * FROM content WHERE slug = ? AND type = 'article' AND status = 'published'");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    die("Article not found: " . htmlspecialchars($slug));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($article['title']); ?> - Dalthaus Photography</title>
    <style>
        body { 
            font-family: 'Gelasio', Georgia, serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px;
            line-height: 1.6;
        }
        h1 { 
            font-family: 'Arimo', Arial, sans-serif;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        .meta {
            color: #7f8c8d;
            margin: 10px 0;
        }
        .content {
            margin-top: 30px;
        }
        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #3498db;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($article['title']); ?></h1>
    
    <div class="meta">
        By <?php echo htmlspecialchars($article['author'] ?? 'Don Althaus'); ?> · 
        <?php echo date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?>
    </div>
    
    <div class="content">
        <?php echo $article['body']; ?>
    </div>
    
    <a href="/" class="back-link">← Back to Homepage</a>
    
    <hr style="margin-top: 50px;">
    <p style="color: #999; font-size: 14px;">
        This is a test page to verify article functionality. 
        Access articles using: /article-test.php?slug=article-slug
    </p>
</body>
</html>
<?php
mysqli_close($conn);
?>