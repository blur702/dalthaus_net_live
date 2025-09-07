<?php
/**
 * Menu Management - Edit View
 * Menu items editor with drag-and-drop hierarchy using SortableJS
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Menu: <?= $this->escape($menu['menu_name']) ?></h2>
                <p class="text-sm text-gray-600 mt-1">
                    Menu ID: <?= $menu['menu_id'] ?>
                </p>
            </div>
            <div class="flex space-x-2">
                <button onclick="addMenuItem()" class="inline-flex items-center px-4 py-2 border border-green-300 text-sm font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Item
                </button>
                <a href="/admin/menus" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                    </svg>
                    Back to Menus
                </a>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Menu Structure -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column: Menu Items Tree -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-md font-medium text-gray-900">Menu Structure</h3>
                    <div class="flex items-center space-x-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                        Drag to reorder
                    </div>
                </div>

                <div id="menu-items" class="space-y-2 min-h-32">
                    <?php if (!empty($menu_items)): ?>
                        <?= $this->renderMenuItems($menu_items) ?>
                    <?php else: ?>
                    <div class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg" id="empty-menu">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <p>No menu items yet</p>
                        <p class="text-sm">Click "Add Item" to get started</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Save Order Button -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <button onclick="saveMenuOrder()" id="save-order-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50" disabled>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Order
                    </button>
                    <span id="order-status" class="ml-2 text-sm text-gray-500"></span>
                </div>
            </div>

            <!-- Right Column: Item Properties -->
            <div>
                <h3 class="text-md font-medium text-gray-900 mb-4">Item Properties</h3>
                
                <div id="item-properties" class="bg-gray-50 p-6 rounded-lg">
                    <div class="text-center text-gray-500">
                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        <p>Select a menu item to edit its properties</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Menu Item Modal -->
<div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Menu Item</h3>
            <form id="addItemForm">
                <div class="space-y-4">
                    <!-- Item Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="item_type" value="page" class="mr-2" checked>
                                <span class="text-sm">Existing Page</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="item_type" value="custom" class="mr-2">
                                <span class="text-sm">Custom Link</span>
                            </label>
                        </div>
                    </div>

                    <!-- Page Selection -->
                    <div id="page-selection">
                        <label for="page_id" class="block text-sm font-medium text-gray-700">Select Page</label>
                        <select name="page_id" id="page_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Choose a page...</option>
                            <?php if (!empty($available_pages)): ?>
                                <?php foreach ($available_pages as $page): ?>
                                <option value="<?= $page['page_id'] ?>" data-title="<?= $this->escape($page['title']) ?>" data-url="/pages/<?= $this->escape($page['url_alias']) ?>">
                                    <?= $this->escape($page['title']) ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Custom Link Fields -->
                    <div id="custom-fields" class="hidden space-y-4">
                        <div>
                            <label for="custom_title" class="block text-sm font-medium text-gray-700">Link Title</label>
                            <input type="text" name="custom_title" id="custom_title" maxlength="100"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="custom_url" class="block text-sm font-medium text-gray-700">URL</label>
                            <input type="url" name="custom_url" id="custom_url"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="https://example.com or /internal-page">
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div>
                        <label for="link_target" class="block text-sm font-medium text-gray-700">Link Target</label>
                        <select name="link_target" id="link_target" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="_self">Same Window</option>
                            <option value="_blank">New Window</option>
                        </select>
                    </div>

                    <div>
                        <label for="css_classes" class="block text-sm font-medium text-gray-700">CSS Classes (Optional)</label>
                        <input type="text" name="css_classes" id="css_classes" maxlength="255"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="button primary-nav">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_published" id="is_published" value="1" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_published" class="ml-2 block text-sm text-gray-700">
                            Published (visible in menu)
                        </label>
                    </div>
                </div>

                <div class="items-center px-4 py-3 mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeAddItemModal()" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let menuChanged = false;
let currentEditingItem = null;
let sortable = null;

// Initialize SortableJS
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

function initializeSortable() {
    const menuItems = document.getElementById('menu-items');
    if (!menuItems) return;
    
    sortable = Sortable.create(menuItems, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onStart: function(evt) {
            // Add visual feedback
            evt.item.classList.add('dragging');
        },
        onEnd: function(evt) {
            evt.item.classList.remove('dragging');
            if (evt.oldIndex !== evt.newIndex) {
                menuChanged = true;
                enableSaveButton();
            }
        },
        // Enable nested sorting
        group: 'nested',
        fallbackOnBody: true,
        swapThreshold: 0.65
    });
    
    // Initialize nested sortables for sub-items
    initializeNestedSortables();
}

function initializeNestedSortables() {
    document.querySelectorAll('.menu-sub-items').forEach(subItems => {
        Sortable.create(subItems, {
            group: 'nested',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                if (evt.oldIndex !== evt.newIndex || evt.from !== evt.to) {
                    menuChanged = true;
                    enableSaveButton();
                }
            }
        });
    });
}

function enableSaveButton() {
    const btn = document.getElementById('save-order-btn');
    btn.disabled = false;
    document.getElementById('order-status').textContent = 'Order changed - click to save';
}

// Add menu item modal functions
function addMenuItem() {
    document.getElementById('addItemModal').classList.remove('hidden');
    document.getElementById('custom_title').focus();
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.add('hidden');
    document.getElementById('addItemForm').reset();
    showPageFields();
}

// Toggle between page and custom link fields
document.querySelectorAll('input[name="item_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'page') {
            showPageFields();
        } else {
            showCustomFields();
        }
    });
});

function showPageFields() {
    document.getElementById('page-selection').classList.remove('hidden');
    document.getElementById('custom-fields').classList.add('hidden');
}

function showCustomFields() {
    document.getElementById('page-selection').classList.add('hidden');
    document.getElementById('custom-fields').classList.remove('hidden');
}

// Auto-fill title when page is selected
document.getElementById('page_id').addEventListener('change', function() {
    // This would be used if we were auto-filling titles
});

// Handle add item form submission
document.getElementById('addItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const itemType = document.querySelector('input[name="item_type"]:checked').value;
    
    formData.append('menu_id', <?= $menu['menu_id'] ?>);
    formData.append('item_type', itemType);
    
    if (itemType === 'page') {
        const pageSelect = document.getElementById('page_id');
        const pageId = pageSelect.value;
        if (!pageId) {
            alert('Please select a page');
            return;
        }
        
        const selectedOption = pageSelect.options[pageSelect.selectedIndex];
        formData.append('page_id', pageId);
        formData.append('title', selectedOption.dataset.title);
        formData.append('url', selectedOption.dataset.url);
    } else {
        const title = document.getElementById('custom_title').value.trim();
        const url = document.getElementById('custom_url').value.trim();
        
        if (!title || !url) {
            alert('Please enter both title and URL for custom link');
            return;
        }
        
        formData.append('title', title);
        formData.append('url', url);
    }
    
    formData.append('link_target', document.getElementById('link_target').value);
    formData.append('css_classes', document.getElementById('css_classes').value);
    formData.append('is_published', document.getElementById('is_published').checked ? '1' : '0');
    formData.append('_token', '<?= $csrf_token ?>');
    
    // Send AJAX request to add item
    fetch('/admin/menus/<?= $menu['menu_id'] ?>/add-item', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the new item
            location.reload();
        } else {
            alert('Error adding menu item: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding menu item');
    });
});

// Save menu order
function saveMenuOrder() {
    const menuItems = document.getElementById('menu-items');
    const items = Array.from(menuItems.children).map((item, index) => {
        const itemData = {
            id: item.dataset.itemId,
            order: index,
            parent_id: null,
            children: []
        };
        
        // Check for sub-items
        const subItems = item.querySelector('.menu-sub-items');
        if (subItems) {
            itemData.children = Array.from(subItems.children).map((subItem, subIndex) => ({
                id: subItem.dataset.itemId,
                order: subIndex,
                parent_id: item.dataset.itemId
            }));
        }
        
        return itemData;
    });
    
    const formData = new FormData();
    formData.append('menu_structure', JSON.stringify(items));
    formData.append('_token', '<?= $csrf_token ?>');
    
    document.getElementById('order-status').textContent = 'Saving...';
    
    fetch('/admin/menus/<?= $menu['menu_id'] ?>/save-order', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            menuChanged = false;
            document.getElementById('save-order-btn').disabled = true;
            document.getElementById('order-status').textContent = 'Order saved successfully';
            setTimeout(() => {
                document.getElementById('order-status').textContent = '';
            }, 3000);
        } else {
            document.getElementById('order-status').textContent = 'Error saving order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('order-status').textContent = 'Error saving order';
    });
}

// Edit menu item
function editMenuItem(itemId) {
    // This would show the item properties in the right panel
    fetch('/admin/menus/items/' + itemId + '/edit', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showItemProperties(data.item);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function showItemProperties(item) {
    const properties = document.getElementById('item-properties');
    properties.innerHTML = `
        <form id="editItemForm" class="space-y-4">
            <input type="hidden" name="item_id" value="${item.item_id}">
            
            <div>
                <label for="edit_title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="edit_title" value="${item.title}" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="edit_url" class="block text-sm font-medium text-gray-700">URL</label>
                <input type="text" name="url" id="edit_url" value="${item.url}" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div>
                <label for="edit_target" class="block text-sm font-medium text-gray-700">Link Target</label>
                <select name="link_target" id="edit_target" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="_self" ${item.link_target === '_self' ? 'selected' : ''}>Same Window</option>
                    <option value="_blank" ${item.link_target === '_blank' ? 'selected' : ''}>New Window</option>
                </select>
            </div>
            
            <div>
                <label for="edit_classes" class="block text-sm font-medium text-gray-700">CSS Classes</label>
                <input type="text" name="css_classes" id="edit_classes" value="${item.css_classes || ''}"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" name="is_published" id="edit_published" value="1" ${item.is_published ? 'checked' : ''}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="edit_published" class="ml-2 block text-sm text-gray-700">Published</label>
            </div>
            
            <div class="flex space-x-2 pt-4 border-t">
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Update Item
                </button>
                <button type="button" onclick="deleteMenuItem(${item.item_id})" class="px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-md hover:bg-red-700">
                    Delete Item
                </button>
            </div>
        </form>
    `;
    
    // Handle form submission
    document.getElementById('editItemForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateMenuItem(item.item_id);
    });
    
    currentEditingItem = item.item_id;
}

function updateMenuItem(itemId) {
    const form = document.getElementById('editItemForm');
    const formData = new FormData(form);
    formData.append('_token', '<?= $csrf_token ?>');
    
    fetch('/admin/menus/items/' + itemId + '/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the menu item display
            location.reload();
        } else {
            alert('Error updating item: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating item');
    });
}

function deleteMenuItem(itemId) {
    if (!confirm('Are you sure you want to delete this menu item?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', '<?= $csrf_token ?>');
    
    fetch('/admin/menus/items/' + itemId + '/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error deleting item: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting item');
    });
}

// Close modal on backdrop click
document.getElementById('addItemModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddItemModal();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddItemModal();
    }
    if (e.ctrlKey || e.metaKey) {
        if (e.key === 's') {
            e.preventDefault();
            if (!document.getElementById('save-order-btn').disabled) {
                saveMenuOrder();
            }
        }
    }
});

// Warn about unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (menuChanged) {
        const confirmationMessage = 'You have unsaved menu changes. Are you sure you want to leave?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}

.sortable-chosen {
    cursor: move;
}

.sortable-drag {
    transform: rotate(5deg);
}

.dragging {
    opacity: 0.8;
    transform: scale(1.05);
}

.menu-item {
    transition: all 0.2s ease;
}

.menu-item:hover {
    background-color: #f9fafb;
}

.menu-item.selected {
    background-color: #dbeafe;
    border-color: #3b82f6;
}
</style>

<?php
// Helper function to render menu items recursively
function renderMenuItems($items, $level = 0) {
    $html = '';
    foreach ($items as $item) {
        $html .= '<div class="menu-item p-3 border rounded-lg bg-white shadow-sm cursor-move" data-item-id="' . $item['item_id'] . '" onclick="editMenuItem(' . $item['item_id'] . ')">';
        
        $html .= '<div class="flex items-center justify-between">';
        $html .= '<div class="flex items-center space-x-2">';
        $html .= '<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"></path></svg>';
        $html .= '<span class="font-medium">' . htmlspecialchars($item['title']) . '</span>';
        if (!$item['is_published']) {
            $html .= '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Draft</span>';
        }
        $html .= '</div>';
        
        $html .= '<div class="text-xs text-gray-500">';
        if ($item['page_id']) {
            $html .= '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">Page</span>';
        } else {
            $html .= '<span class="bg-green-100 text-green-800 px-2 py-1 rounded">Custom</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="mt-1 text-sm text-gray-600 truncate">' . htmlspecialchars($item['url']) . '</div>';
        
        // Render sub-items if any
        if (!empty($item['children'])) {
            $html .= '<div class="menu-sub-items mt-2 ml-4 space-y-1">';
            $html .= renderMenuItems($item['children'], $level + 1);
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    return $html;
}

// Make the function available to the template
if (!function_exists('renderMenuItems')) {
    $this->renderMenuItems = function($items, $level = 0) {
        return renderMenuItems($items, $level);
    };
}
?>
