<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $this->escape($page_title) : 'Authentication' ?></title>

    <!-- Tailwind CSS - Production Build -->
    <link rel="stylesheet" href="/assets/css/output.css?v=<?= filemtime(__DIR__ . '/../../../assets/css/output.css') ?>">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <?= $content ?>
    </div>
</body>
</html>
