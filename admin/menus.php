<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/Menu.php';
require_once __DIR__ . '/../src/Models/MenuItem.php';
require_once __DIR__ . '/../src/Models/Content.php';
require_once __DIR__ . '/../src/Models/Page.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\Menu;
use CMS\Models\MenuItem;
use CMS\Models\Content;
use CMS\Models\Page;
use CMS\Utils\Security;

// Check authentication
Auth::requireLogin();

// Initialize variables
$error = '';
$success = '';
$selectedMenuId = (int) ($_GET['menu_id'] ?? 0);
$selectedMenu = null;
$menuItems = [];

// Get all menus
$allMenus = Menu::getAllWithCounts();

// Set default menu if none selected
if (empty($selectedMenuId) && !empty($allMenus)) {
    $selectedMenuId = $allMenus[0]->menu_id;
}

// Get selected menu and its items
if ($selectedMenuId > 0) {
    $menuData = Menu::getWithItems($selectedMenuId);
    if ($menuData) {
        $selectedMenu = $menuData['menu'];
        $menuItems = $menuData['items'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_menu':
                $menuName = trim($_POST['menu_name'] ?? '');
                $validationErrors = Menu::validateMenuData(['menu_name' => $menuName]);
                
                if (empty($validationErrors)) {
                    try {
                        $newMenu = Menu::create(['menu_name' => $menuName]);
                        $success = 'Menu created successfully.';
                        $selectedMenuId = $newMenu->menu_id;
                        header("Location: ?menu_id={$selectedMenuId}&success=created");
                        exit;
                    } catch (Exception $e) {
                        $error = 'Failed to create menu: ' . $e->getMessage();
                    }
                } else {
                    $error = implode(', ', $validationErrors);
                }
                break;

            case 'add_menu_item':
                if ($selectedMenuId > 0) {
                    $itemData = [
                        'menu_id' => $selectedMenuId,
                        'label' => trim($_POST['label'] ?? ''),
                        'link' => trim($_POST['link'] ?? ''),
                        'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null
                    ];

                    $validationErrors = MenuItem::validateItemData($itemData);
                    
                    if (empty($validationErrors)) {
                        try {
                            MenuItem::createItem($itemData);
                            $success = 'Menu item added successfully.';
                            header("Location: ?menu_id={$selectedMenuId}&success=item_added");
                            exit;
                        } catch (Exception $e) {
                            $error = 'Failed to add menu item: ' . $e->getMessage();
                        }
                    } else {
                        $error = implode(', ', $validationErrors);
                    }
                }
                break;

            case 'delete_menu_item':
                $itemId = (int) ($_POST['item_id'] ?? 0);
                if ($itemId > 0) {
                    $menuItem = MenuItem::find($itemId);
                    if ($menuItem && $menuItem->delete()) {
                        $success = 'Menu item deleted successfully.';
                        header("Location: ?menu_id={$selectedMenuId}&success=item_deleted");
                        exit;
                    } else {
                        $error = 'Failed to delete menu item.';
                    }
                }
                break;

            case 'update_menu_order':
                $orderData = json_decode($_POST['order'] ?? '', true);
                if (is_array($orderData)) {
                    $sanitizedOrder = [];
                    foreach ($orderData as $item) {
                        if (isset($item['id']) && isset($item['position'])) {
                            $sanitizedOrder[(int) $item['id']] = (int) $item['position'];
                        }
                    }
                    
                    if (MenuItem::updateSortOrder($sanitizedOrder)) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true]);
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Failed to update order']);
                    }
                    exit;
                }
                break;
        }
    }
}

// Get available content for linking
$availableContent = MenuItem::getAvailableContent();

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <?php include 'includes/header.php'; ?>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Menu Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage navigation menus for your website
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button type="button" onclick="openCreateMenuModal()" 
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                    Create Menu
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="rounded-md bg-red-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Menu Selection -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Available Menus</h3>
                        
                        <?php if (empty($allMenus)): ?>
                            <p class="text-gray-500 text-sm">No menus available. Create your first menu to get started.</p>
                        <?php else: ?>
                            <ul class="space-y-2">
                                <?php foreach ($allMenus as $menu): ?>
                                    <li>
                                        <a href="?menu_id=<?= $menu->menu_id ?>" 
                                           class="<?= $menu->menu_id === $selectedMenuId ? 'bg-blue-50 text-blue-700 border-blue-200' : 'hover:bg-gray-50' ?> block px-3 py-2 border border-gray-200 rounded-md">
                                            <div class="flex justify-between items-center">
                                                <span class="font-medium"><?= htmlspecialchars($menu->menu_name) ?></span>
                                                <span class="text-sm text-gray-500"><?= $menu->item_count ?> items</span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Menu Item Form -->
                <?php if ($selectedMenu): ?>
                    <div class="bg-white shadow rounded-lg mt-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Menu Item</h3>
                            
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="add_menu_item">
                                
                                <div>
                                    <label for="label" class="block text-sm font-medium text-gray-700">Label *</label>
                                    <input type="text" name="label" id="label" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="link" class="block text-sm font-medium text-gray-700">Link *</label>
                                    <input type="text" name="link" id="link" required placeholder="/path or https://..."
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Item</label>
                                    <select name="parent_id" id="parent_id" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Top Level</option>
                                        <?php 
                                        $parentOptions = MenuItem::getParentOptions($selectedMenuId);
                                        foreach ($parentOptions as $option): ?>
                                            <option value="<?= $option['value'] ?>"><?= htmlspecialchars($option['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" 
                                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Add Menu Item
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Menu Items -->
            <div class="lg:col-span-2">
                <?php if ($selectedMenu): ?>
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Menu: <?= htmlspecialchars($selectedMenu['menu_name']) ?>
                                </h3>
                                <span class="text-sm text-gray-500"><?= count($menuItems) ?> items</span>
                            </div>
                            
                            <?php if (empty($menuItems)): ?>
                                <div class="text-center py-8">
                                    <i class="fas fa-bars text-gray-400 text-4xl mb-4"></i>
                                    <p class="text-gray-500">This menu has no items yet.</p>
                                    <p class="text-sm text-gray-400">Add your first menu item using the form on the left.</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-4 p-3 bg-blue-50 rounded-md">
                                    <p class="text-sm text-blue-700">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Drag and drop to reorder menu items. Changes are saved automatically.
                                    </p>
                                </div>
                                
                                <ul id="sortable-menu" class="space-y-2">
                                    <?php foreach ($menuItems as $item): ?>
                                        <li class="sortable-item border border-gray-200 rounded-md p-3 bg-white" data-id="<?= $item['item_id'] ?>">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <i class="fas fa-grip-vertical text-gray-400 mr-3 cursor-move"></i>
                                                    <div>
                                                        <h4 class="font-medium text-gray-900"><?= htmlspecialchars($item['label']) ?></h4>
                                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($item['link']) ?></p>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center space-x-2">
                                                    <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                                        <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="delete_menu_item">
                                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($item['children'])): ?>
                                                <ul class="ml-6 mt-2 space-y-1">
                                                    <?php foreach ($item['children'] as $child): ?>
                                                        <li class="border border-gray-100 rounded p-2 bg-gray-50">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <span class="text-sm font-medium"><?= htmlspecialchars($child['label']) ?></span>
                                                                    <span class="text-xs text-gray-500 ml-2"><?= htmlspecialchars($child['link']) ?></span>
                                                                </div>
                                                                <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                                                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                                                    <input type="hidden" name="action" value="delete_menu_item">
                                                                    <input type="hidden" name="item_id" value="<?= $child['item_id'] ?>">
                                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6 text-center">
                            <i class="fas fa-bars text-gray-400 text-5xl mb-4"></i>
                            <p class="text-lg font-medium text-gray-900">No Menu Selected</p>
                            <p class="text-gray-500">Select a menu from the left sidebar or create a new menu to get started.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Menu Modal -->
    <div id="create-menu-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New Menu</h3>
                    <button type="button" onclick="closeCreateMenuModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" id="create-menu-form">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="create_menu">
                    
                    <div class="mb-4">
                        <label for="modal-menu-name" class="block text-sm font-medium text-gray-700">Menu Name *</label>
                        <input type="text" name="menu_name" id="modal-menu-name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="mt-1 text-sm text-gray-500">Use letters, numbers, underscores, and hyphens only</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateMenuModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Create Menu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Modal functions
    function openCreateMenuModal() {
        document.getElementById('create-menu-modal').classList.remove('hidden');
        document.getElementById('modal-menu-name').focus();
    }

    function closeCreateMenuModal() {
        document.getElementById('create-menu-modal').classList.add('hidden');
        document.getElementById('create-menu-form').reset();
    }

    // Initialize SortableJS for menu items
    const sortableList = document.getElementById('sortable-menu');
    if (sortableList) {
        Sortable.create(sortableList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            handle: '.fa-grip-vertical',
            onEnd: function(evt) {
                updateMenuOrder();
            }
        });
    }

    // Update menu item order
    function updateMenuOrder() {
        const items = document.querySelectorAll('.sortable-item');
        const orderData = [];
        
        items.forEach((item, index) => {
            orderData.push({
                id: parseInt(item.dataset.id),
                position: index + 1
            });
        });

        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'update_menu_order',
                _token: '<?= $csrfToken ?>',
                order: JSON.stringify(orderData)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to update menu order');
                // Optionally show user notification
            }
        })
        .catch(error => {
            console.error('Error updating menu order:', error);
        });
    }

    // Quick link helpers
    function insertQuickLink(type, id, title) {
        const linkField = document.getElementById('link');
        const labelField = document.getElementById('label');
        
        if (type === 'content') {
            linkField.value = `/content/${id}`;
        } else if (type === 'page') {
            linkField.value = `/page/${id}`;
        }
        
        if (!labelField.value) {
            labelField.value = title;
        }
    }

    // Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateMenuModal();
        }
    });
    </script>
</body>
</html>