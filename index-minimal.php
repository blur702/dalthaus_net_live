<?php
// Minimal working index - bypass all complexity

// Direct database config (no includes)
$db_host = 'localhost';
$db_name = 'dalthaus_photocms';
$db_user = 'dalthaus_photocms';
$db_pass = 'f-I*GSo^Urt*k*&#';

// Connect to database
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

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

<?php if ($conn): ?>
    <div class="status">
        ✅ Database connected successfully! The site is operational.
    </div>
    
    <div class="content-grid">
    <?php
    // Get published content
    $query = "SELECT * FROM content WHERE status = 'published' ORDER BY created_at DESC LIMIT 6";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
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
    
    mysqli_close($conn);
    ?>
    </div>
    
<?php else: ?>
    <div class="status error">
        ❌ Database connection failed. Please check configuration.
    </div>
<?php endif; ?>

</body>
</html>