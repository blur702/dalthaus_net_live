<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $this->escape($page_title . ' - Admin') : 'Admin' ?> - <?= $this->escape($settings['site_title'] ?? 'CMS') ?></title>
    
    <?php if (!empty($settings['favicon'])): ?>
    <link rel="icon" href="<?= $this->escape('/uploads/' . $settings['favicon']) ?>">
    <?php endif; ?>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- TinyMCE GPL Version from jsDelivr -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- SortableJS for drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <!-- Axios for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <style>
        body {
            background-color: rgb(248, 248, 248);
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .dropdown-menu {
            display: none;
        }
        .autosave-indicator {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        .tox-tinymce {
            border-radius: 0.375rem;
            border: 1px solid #D1D5DB;
        }
    </style>
</head>
<body style="background-color: rgb(248, 248, 248);">
    <!-- Admin Header -->
    <header class="bg-white border-b border-gray-300">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Left Navigation -->
                <nav class="flex items-center space-x-8">
                    <!-- Articles Dropdown -->
                    <div class="dropdown relative">
                        <a href="/admin/content?type=article" class="text-gray-900 hover:text-gray-700 font-medium">
                            Articles
                        </a>
                        <div class="dropdown-menu absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50">
                            <a href="/admin/content/create?type=article" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create</a>
                            <a href="/admin/content?type=article" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                            <a href="/admin/reorder?type=article" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Reorder</a>
                        </div>
                    </div>
                    
                    <!-- Photobooks Dropdown -->
                    <div class="dropdown relative">
                        <a href="/admin/content?type=photobook" class="text-gray-900 hover:text-gray-700 font-medium">
                            Photobooks
                        </a>
                        <div class="dropdown-menu absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50">
                            <a href="/admin/content/create?type=photobook" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create</a>
                            <a href="/admin/content?type=photobook" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                            <a href="/admin/reorder?type=photobook" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Reorder</a>
                        </div>
                    </div>
                    
                    <!-- Pages Dropdown -->
                    <div class="dropdown relative">
                        <a href="/admin/pages" class="text-gray-900 hover:text-gray-700 font-medium">
                            Pages
                        </a>
                        <div class="dropdown-menu absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50">
                            <a href="/admin/pages/create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create</a>
                            <a href="/admin/pages" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                        </div>
                    </div>
                    
                    <!-- Settings -->
                    <a href="/admin/settings" class="text-gray-900 hover:text-gray-700 font-medium">
                        Settings
                    </a>
                </nav>
                
                <!-- Center - Autosave Indicator -->
                <div class="flex-1 flex justify-center">
                    <div id="autosave-indicator" class="hidden items-center space-x-2">
                        <svg class="autosave-indicator w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span id="autosave-timestamp" class="text-sm text-green-600"></span>
                    </div>
                </div>
                
                <!-- Right - User Menu -->
                <div class="flex items-center space-x-4">
                    <div class="dropdown relative">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                            <span class="text-sm font-medium"><?= $this->escape($current_user['username'] ?? 'Admin') ?></span>
                            <div class="w-8 h-8 bg-black rounded-full"></div>
                        </button>
                        <div class="dropdown-menu absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg z-50 min-w-[160px]">
                            <a href="/admin/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                            <a href="/admin/users/<?= $current_user['user_id'] ?? '' ?>/edit" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <hr class="my-1">
                            <a href="/" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View Site</a>
                            <hr class="my-1">
                            <form action="/admin/logout" method="POST" class="block">
                                <input type="hidden" name="_token" value="<?= $this->escape($csrf_token ?? '') ?>">
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (isset($flash) && !empty($flash)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <?php foreach ($flash as $type => $message): ?>
        <div class="flash-message mb-4 p-4 rounded-md <?= $type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : ($type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-blue-50 text-blue-800 border border-blue-200') ?>">
            <?= $this->escape($message) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= $content ?>
    </main>

    <script>
        // TinyMCE Initialization
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('body')) {
                tinymce.init({
                    selector: 'textarea#body',
                    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount pagebreak autosave',
                    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | pagebreak | help',
                    height: 500,
                    menubar: false,
                    statusbar: false,
                    autosave_interval: '60s',
                    autosave_prefix: 'tinymce-autosave-{path}{query}-{id}-',
                    autosave_restore_when_empty: true,
                    autosave_retention: '30m',
                    setup: function (editor) {
                        // This ensures the editor's content is saved to the textarea before the form is submitted.
                        editor.on('init', () => {
                            const form = editor.getElement().form;
                            if (form) {
                                form.addEventListener('submit', () => {
                                    editor.save();
                                });
                            }
                        });

                        // Handle autosave events
                        editor.on('Autosave', function (e) {
                            const indicator = document.getElementById('autosave-indicator');
                            const timestamp = document.getElementById('autosave-timestamp');
                            if (indicator && timestamp) {
                                timestamp.textContent = 'Saved at ' + new Date().toLocaleTimeString();
                                indicator.classList.remove('hidden');
                                indicator.classList.add('flex');
                                setTimeout(() => {
                                    indicator.classList.add('hidden');
                                    indicator.classList.remove('flex');
                                }, 3000);
                            }
                        });
                    }
                });
            }
        });

        // Auto-dismiss flash messages after 5 seconds
        setTimeout(function() {
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(message => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);

        // Dropdown menu functionality
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            let timeout;
            const menu = dropdown.querySelector('.dropdown-menu');
            
            dropdown.addEventListener('mouseenter', () => {
                clearTimeout(timeout);
                menu.style.display = 'block';
            });
            
            dropdown.addEventListener('mouseleave', () => {
                timeout = setTimeout(() => {
                    menu.style.display = 'none';
                }, 200);
            });
        });
    </script>
</body>
</html>
