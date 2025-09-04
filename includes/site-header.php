<?php
/**
 * Site Header Template
 * Contains the HTML head section with dynamic title support
 */

// Set default values if not set
if (!isset($site_title)) {
    $site_title = 'Dalthaus Photography';
}
if (!isset($page_title)) {
    $page_title = '';
}

// Build final title
$final_title = !empty($page_title) ? $page_title . ' | ' . $site_title : $site_title;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($final_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&family=Gelasio:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <?php if (file_exists('assets/css/main.css')): ?>
        <link rel="stylesheet" href="/assets/css/main.css">
    <?php endif; ?>
</head>
<body>