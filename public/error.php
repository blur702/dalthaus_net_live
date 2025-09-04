<?php
// Error 404 Page
if (!headers_sent()) {
    header("HTTP/1.0 404 Not Found");
}

// Get site settings if available
$site_title = 'Dalthaus Photography';
if (function_exists('getSetting')) {
    $site_title = getSetting('site_title', 'Dalthaus Photography');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo htmlspecialchars($site_title); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        h1 {
            color: #2c3e50;
            font-size: 72px;
            margin: 0 0 20px;
        }
        h2 {
            color: #555;
            font-size: 24px;
            margin: 0 0 20px;
        }
        p {
            color: #777;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        a {
            display: inline-block;
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        a:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>Sorry, the page you are looking for could not be found. It might have been moved, renamed, or doesn't exist.</p>
        <a href="/">Return to Homepage</a>
    </div>
</body>
</html>