<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($page_title ?? 'Admin') ?> - <?= $this->escape($settings['site_title'] ?? 'CMS') ?></title>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📝</text></svg>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body { background-color: #f8f9fa; }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu { display: none; }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <nav class="flex items-center space-x-8">
                    <a href="/admin/dashboard" class="text-gray-900 font-bold text-lg">CMS</a>
                    <a href="/admin/content?type=article" class="text-gray-700 hover:text-gray-900">Articles</a>
                    <a href="/admin/content?type=photobook" class="text-gray-700 hover:text-gray-900">Photobooks</a>
                    <a href="/admin/pages" class="text-gray-700 hover:text-gray-900">Pages</a>
                    <a href="/admin/menus" class="text-gray-700 hover:text-gray-900">Menus</a>
                    <a href="/admin/users" class="text-gray-700 hover:text-gray-900">Users</a>
                    <a href="/admin/settings" class="text-gray-700 hover:text-gray-900">Settings</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <div class="dropdown relative">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <span class="text-sm font-medium"><?= $this->escape($current_user['username'] ?? 'Admin') ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div class="dropdown-menu absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50 min-w-[160px]">
                            <a href="/" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Site</a>
                            <hr class="my-1">
                            <form action="/admin/logout" method="POST" class="block">
                                <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="flash-message p-4 rounded-md <?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
            <?= $this->escape($flash['message']) ?>
        </div>
    </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $content ?? '' ?>
    </main>

    <script>
        // Page functionality
        document.addEventListener('DOMContentLoaded', function () {
            
            // Auto-dismiss flash messages
            setTimeout(function() {
                const flashMessage = document.querySelector('.flash-message');
                if (flashMessage) {
                    flashMessage.style.transition = 'opacity 0.5s';
                    flashMessage.style.opacity = '0';
                    setTimeout(() => flashMessage.remove(), 500);
                }
            }, 5000);
            
            // Auto-generate URL alias from title (for forms that have it)
            const titleInput = document.getElementById('title');
            const urlAliasInput = document.getElementById('url_alias');
            if (titleInput && urlAliasInput) {
                titleInput.addEventListener('input', function() {
                    if (!urlAliasInput.dataset.manuallyEdited) {
                        urlAliasInput.value = this.value.toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/\s+/g, '-')
                            .replace(/-+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    }
                });
                urlAliasInput.addEventListener('input', function() {
                    this.dataset.manuallyEdited = 'true';
                });
            }
        });
    </script>
</body>
</html>