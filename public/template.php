<?php
// This is a template file for public facing pages
// It includes the header and footer, and a content area
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Dalthaus Photography'); ?></title>
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;600&family=Gelasio:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="page-wrapper">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="site-main">
        <?php
        // The content of the page will be output here
        if (isset($page_content)) {
            echo $page_content;
        }
        ?>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
