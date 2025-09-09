<?php
// Ensure this file is only included, not accessed directly
if (!defined('PHP_SELF') && basename($_SERVER['PHP_SELF']) === 'header.php') {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed.');
}

// Get current user info
$currentUser = CMS\Utils\Auth::getUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Navigation -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="/admin/dashboard.php" class="flex items-center">
                        <i class="fas fa-cogs text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-900">CMS Admin</span>
                    </a>
                </div>
                
                <nav class="hidden md:ml-8 md:flex md:space-x-8">
                    <a href="dashboard.php" 
                       class="<?= $currentPage === 'dashboard' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-tachometer-alt mr-1"></i>
                        Dashboard
                    </a>
                    
                    <a href="content.php" 
                       class="<?= in_array($currentPage, ['content', 'content-form', 'content-reorder']) ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-file-text mr-1"></i>
                        Content
                    </a>
                    
                    <a href="pages.php" 
                       class="<?= in_array($currentPage, ['pages', 'pages-form']) ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-copy mr-1"></i>
                        Pages
                    </a>
                    
                    <a href="menus.php" 
                       class="<?= in_array($currentPage, ['menus', 'menus-form']) ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-bars mr-1"></i>
                        Menus
                    </a>
                    
                    <a href="users.php" 
                       class="<?= in_array($currentPage, ['users', 'users-form']) ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-users mr-1"></i>
                        Users
                    </a>
                    
                    <a href="settings.php" 
                       class="<?= $currentPage === 'settings' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?> px-3 py-2 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 transition-all duration-200">
                        <i class="fas fa-cog mr-1"></i>
                        Settings
                    </a>
                </nav>
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-4">
                <!-- View Site Link -->
                <a href="/" target="_blank" 
                   class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium flex items-center transition-colors duration-200">
                    <i class="fas fa-external-link-alt mr-1"></i>
                    <span class="hidden sm:inline">View Site</span>
                </a>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="sr-only">Open user menu</span>
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                            <span class="text-sm font-medium text-white">
                                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 1)) ?>
                            </span>
                        </div>
                        <span class="ml-2 text-gray-700 text-sm font-medium hidden sm:block">
                            <?= htmlspecialchars($currentUser['username'] ?? 'User') ?>
                        </span>
                        <i class="fas fa-chevron-down ml-1 text-gray-400 text-xs"></i>
                    </button>

                    <div x-show="open" 
                         @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50">
                        <div class="px-4 py-3">
                            <p class="text-sm text-gray-900"><?= htmlspecialchars($currentUser['username'] ?? '') ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                        </div>
                        
                        <div class="py-1">
                            <a href="profile.php" 
                               class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                <i class="fas fa-user mr-3 text-gray-400 group-hover:text-gray-500"></i>
                                Profile Settings
                            </a>
                        </div>
                        
                        <div class="py-1">
                            <form method="POST" action="logout.php" class="block">
                                <button type="submit" 
                                        class="group flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                    <i class="fas fa-sign-out-alt mr-3 text-gray-400 group-hover:text-gray-500"></i>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <button type="button" 
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                        x-data="{ open: false }" 
                        @click="open = !open">
                    <span class="sr-only">Open main menu</span>
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="md:hidden" x-data="{ open: false }" x-show="open" @click.away="open = false">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-gray-200">
                <a href="dashboard.php" 
                   class="<?= $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Dashboard
                </a>
                
                <a href="content.php" 
                   class="<?= in_array($currentPage, ['content', 'content-form', 'content-reorder']) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-file-text mr-2"></i>
                    Content
                </a>
                
                <a href="pages.php" 
                   class="<?= in_array($currentPage, ['pages', 'pages-form']) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-copy mr-2"></i>
                    Pages
                </a>
                
                <a href="menus.php" 
                   class="<?= in_array($currentPage, ['menus', 'menus-form']) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-bars mr-2"></i>
                    Menus
                </a>
                
                <a href="users.php" 
                   class="<?= in_array($currentPage, ['users', 'users-form']) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-users mr-2"></i>
                    Users
                </a>
                
                <a href="settings.php" 
                   class="<?= $currentPage === 'settings' ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-cog mr-2"></i>
                    Settings
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>