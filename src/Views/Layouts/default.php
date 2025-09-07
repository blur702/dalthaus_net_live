<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $this->escape($page_title . ' - ' . ($settings['site_title'] ?? 'CMS')) : $this->escape($settings['site_title'] ?? 'CMS') ?></title>
    
    <?php if (isset($meta_description) && $meta_description): ?>
    <meta name="description" content="<?= $this->escape($meta_description) ?>">
    <?php endif; ?>
    
    <?php if (isset($meta_keywords) && $meta_keywords): ?>
    <meta name="keywords" content="<?= $this->escape($meta_keywords) ?>">
    <?php endif; ?>
    
    <?php if (!empty($settings['favicon'])): ?>
    <link rel="icon" href="<?= $this->escape('/uploads/' . $settings['favicon']) ?>">
    <?php endif; ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <style>
        /* Dead-flat-simple design philosophy */
        body {
            font-family: Georgia, serif;
            font-size: 12pt;
            line-height: 1.15;
            color: rgb(20, 20, 20);
            /* Background color from design specs: Almost imperceptible gray */
            background-color: rgb(248, 248, 248);
        }
        
        /* Container uses Tailwind's max-w-7xl (1280px) */
        
        .teaser-image {
            aspect-ratio: 4 / 3;
            object-fit: cover;
            background-color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.125rem;
        }
        
        .content-text {
            text-align: left;
            margin: 0;
            padding: 0;
        }
        
        .content-text p {
            margin: 0 0 1em 0;
        }
        
        .read-more {
            border: 1px solid #333;
            padding: 0.5rem 1rem;
            text-decoration: none;
            display: inline-block;
            color: #333;
            background: white;
            transition: all 0.2s ease;
        }
        
        .read-more:hover {
            background: #333;
            color: white;
        }
        
        /* Typography from design specs */
        h1, h2, h3, h4, h5, h6 {
            font-family: Arial, sans-serif;
        }
        
        .overlay-text {
            color: white;
        }
        
        /* Side menu styles */
        .side-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .side-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .side-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: rgb(248, 248, 248);
            z-index: 999;
            transition: right 0.3s ease;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .side-menu.active {
            right: 0;
        }
        
        .side-menu-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .side-menu-content {
            padding: 1.5rem;
        }
        
        .close-menu {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Pagination styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.25rem;
            margin: 2rem 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #333;
            color: #333;
            text-decoration: none;
            background: white;
            min-width: 2.5rem;
            text-align: center;
        }
        
        .pagination a:hover {
            background: #333;
            color: white;
        }
        
        .pagination .current {
            background: #333;
            color: white;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: #ccc;
            color: #ccc;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold text-gray-900" style="font-family: Arial, sans-serif;">
                        <a href="/" class="hover:text-gray-700 no-underline">
                            <?= $this->escape($settings['site_title'] ?? 'SITE TITLE') ?>
                        </a>
                    </h1>
                    <?php if (!empty($settings['site_motto'])): ?>
                    <p class="text-gray-900 text-sm mt-0" style="font-family: Arial, sans-serif;">
                        <?= $this->escape($settings['site_motto']) ?>
                    </p>
                    <?php else: ?>
                    <p class="text-gray-900 text-sm mt-0" style="font-family: Arial, sans-serif;">
                        SITE MOTTO
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- Hamburger Menu (always visible) -->
                <button class="p-2 bg-transparent border-0" onclick="openMenu()" aria-label="Toggle menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="text-center text-gray-900 text-xs">
                <p>copyright <?= date('Y') ?>, <?= $this->escape($settings['site_title'] ?? 'site title') ?></p>
                <?php if (!empty($footer_menu)): ?>
                <div class="mt-2">
                    <?php foreach ($footer_menu as $item): ?>
                        <a href="<?= $this->escape($item['url']) ?>" class="text-gray-900 hover:text-gray-600 mx-2 no-underline">
                            <?= $this->escape($item['title']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <!-- Side Menu Overlay -->
    <div id="menuOverlay" class="side-menu-overlay" onclick="closeMenu()"></div>
    
    <!-- Side Menu -->
    <div id="sideMenu" class="side-menu">
        <div class="side-menu-header">
            <div></div>
            <button class="close-menu" onclick="closeMenu()" aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="side-menu-content">
            <nav>
                <ul class="space-y-4" style="font-family: Arial, sans-serif;">
                    <li><a href="/" class="block text-gray-900 hover:text-gray-600 py-2 text-lg no-underline">Home</a></li>
                    <li><a href="/articles" class="block text-gray-900 hover:text-gray-600 py-2 text-lg no-underline">Articles</a></li>
                    <li><a href="/photobooks" class="block text-gray-900 hover:text-gray-600 py-2 text-lg no-underline">Photobooks</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <script>
        // Prevent custom element redefinition errors from browser extensions
        (function() {
            const originalDefine = window.customElements ? window.customElements.define : null;
            if (originalDefine) {
                window.customElements.define = function(name, constructor, options) {
                    if (!window.customElements.get(name)) {
                        try {
                            originalDefine.call(window.customElements, name, constructor, options);
                        } catch (e) {
                            console.warn('Custom element registration blocked:', name);
                        }
                    }
                };
            }
        })();
        
        function openMenu() {
            document.getElementById('sideMenu').classList.add('active');
            document.getElementById('menuOverlay').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent body scroll when menu is open
        }
        
        function closeMenu() {
            document.getElementById('sideMenu').classList.remove('active');
            document.getElementById('menuOverlay').classList.remove('active');
            document.body.style.overflow = ''; // Restore body scroll
        }

        // Image lazy loading implementation
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                images.forEach(function(img) {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                images.forEach(function(img) {
                    img.src = img.dataset.src;
                });
            }
        });
    </script>
</body>
</html>
