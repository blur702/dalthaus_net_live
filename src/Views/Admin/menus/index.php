<?php
/**
 * Menu Management - Index View
 * Lists all menus and allows basic menu management
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Menu Management</h2>
            <div class="flex space-x-2">
                <button onclick="createMenu()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Menu
                </button>
            </div>
        </div>
    </div>

    <!-- Menus List -->
    <div class="overflow-hidden">
        <?php if (!empty($menus)): ?>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Menu Name
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Location
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Items
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Updated
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($menus as $menu): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8 bg-gray-100 rounded flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= $this->escape($menu['menu_name']) ?>
                                </div>
                                <?php if (!empty($menu['description'])): ?>
                                <div class="text-sm text-gray-500">
                                    <?= $this->escape($menu['description']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Header
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="flex items-center">
                            <span class="mr-2"><?= $menu['item_count'] ?? 0 ?> items</span>
                            <?php if ($menu['item_count'] > 0): ?>
                            <span class="text-xs text-gray-400">(<?= $menu['published_count'] ?? 0 ?> published)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        -
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="/admin/menus/<?= $menu['menu_id'] ?>" class="text-blue-600 hover:text-blue-900">
                                Edit Items
                            </a>
                            <button onclick="toggleMenuStatus(<?= $menu['menu_id'] ?>, '<?= $this->escape($menu['menu_name']) ?>')" class="text-purple-600 hover:text-purple-900">
                                Toggle
                            </button>
                            <button onclick="duplicateMenu(<?= $menu['menu_id'] ?>, '<?= $this->escape($menu['menu_name']) ?>')" class="text-green-600 hover:text-green-900">
                                Duplicate
                            </button>
                            <button onclick="deleteMenu(<?= $menu['menu_id'] ?>, '<?= $this->escape($menu['menu_name']) ?>')" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No menus found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first navigation menu.</p>
            <div class="mt-6">
                <button onclick="createMenu()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Menu
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Available Menu Locations -->
    <?php if (!empty($menu_locations)): ?>
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Available Menu Locations</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($menu_locations as $location => $details): ?>
            <div class="flex items-center justify-between p-3 bg-white rounded border">
                <div>
                    <div class="text-sm font-medium text-gray-900"><?= ucfirst($location) ?></div>
                    <div class="text-xs text-gray-500"><?= $this->escape($details['description']) ?></div>
                </div>
                <div class="text-xs">
                    <?php if (!empty($details['assigned_menu'])): ?>
                    <span class="text-green-600">Assigned</span>
                    <?php else: ?>
                    <span class="text-gray-400">Available</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Create Menu Modal -->
<div id="createMenuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Menu</h3>
            <form id="createMenuForm" method="POST" action="/admin/menus/store">
                <?= $this->csrfField() ?>
                <div class="space-y-4">
                    <div>
                        <label for="menu_name" class="block text-sm font-medium text-gray-700">Menu Name</label>
                        <input type="text" name="name" id="menu_name" required maxlength="100"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="e.g., Main Navigation">
                    </div>
                    <div>
                        <label for="menu_description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                        <textarea name="description" id="menu_description" rows="2" maxlength="255"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="Brief description of this menu's purpose"></textarea>
                    </div>
                    <div>
                        <label for="menu_location" class="block text-sm font-medium text-gray-700">Menu Location</label>
                        <select name="location" id="menu_location" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select a location</option>
                            <?php if (!empty($available_locations)): ?>
                                <?php foreach ($available_locations as $location => $details): ?>
                                <option value="<?= $location ?>"><?= ucfirst($location) ?> - <?= $this->escape($details['description']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="menu_active" value="1" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="menu_active" class="ml-2 block text-sm text-gray-700">
                            Make this menu active immediately
                        </label>
                    </div>
                </div>
                <div class="items-center px-4 py-3 mt-6 flex justify-end space-x-2">
                    <button type="button" onclick="closeCreateMenuModal()" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        Create Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Menu Modal -->
<div id="deleteMenuModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Delete Menu</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete "<span id="deleteMenuTitle"></span>"? This will also delete all menu items. This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteMenuForm" method="POST" action="" class="inline">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 mr-2">
                        Delete
                    </button>
                </form>
                <button onclick="closeDeleteMenuModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div id="toggleStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2" id="toggleStatusTitle">Toggle Menu Status</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="toggleStatusMessage">
                    <!-- Message will be set by JavaScript -->
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="toggleStatusForm" method="POST" action="" class="inline">
                    <?= $this->csrfField() ?>
                    <input type="hidden" name="is_active" id="toggleStatusValue" value="">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 mr-2" id="toggleStatusButton">
                        Confirm
                    </button>
                </form>
                <button onclick="closeToggleStatusModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function createMenu() {
    document.getElementById('createMenuModal').classList.remove('hidden');
    document.getElementById('menu_name').focus();
}

function closeCreateMenuModal() {
    document.getElementById('createMenuModal').classList.add('hidden');
    document.getElementById('createMenuForm').reset();
}

function deleteMenu(menuId, title) {
    document.getElementById('deleteMenuTitle').textContent = title;
    document.getElementById('deleteMenuForm').action = '/admin/menus/' + menuId + '/delete';
    document.getElementById('deleteMenuModal').classList.remove('hidden');
}

function closeDeleteMenuModal() {
    document.getElementById('deleteMenuModal').classList.add('hidden');
}

function toggleMenuStatus(menuId, title, isActive) {
    const newStatus = !isActive;
    const action = newStatus ? 'activate' : 'deactivate';
    
    document.getElementById('toggleStatusTitle').textContent = (newStatus ? 'Activate' : 'Deactivate') + ' Menu';
    document.getElementById('toggleStatusMessage').textContent = 
        'Are you sure you want to ' + action + ' the menu "' + title + '"?';
    document.getElementById('toggleStatusButton').textContent = newStatus ? 'Activate' : 'Deactivate';
    document.getElementById('toggleStatusValue').value = newStatus ? '1' : '0';
    document.getElementById('toggleStatusForm').action = '/admin/menus/' + menuId + '/toggle-status';
    document.getElementById('toggleStatusModal').classList.remove('hidden');
}

function closeToggleStatusModal() {
    document.getElementById('toggleStatusModal').classList.add('hidden');
}

function duplicateMenu(menuId, title) {
    if (confirm('Create a copy of the menu "' + title + '"? The duplicate will be created as inactive.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/menus/' + menuId + '/duplicate';
        
        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = '_token';
        csrfField.value = '<?= $csrf_token ?>';
        
        form.appendChild(csrfField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals on backdrop click
document.getElementById('createMenuModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateMenuModal();
    }
});

document.getElementById('deleteMenuModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteMenuModal();
    }
});

document.getElementById('toggleStatusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeToggleStatusModal();
    }
});

// Handle keyboard events
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateMenuModal();
        closeDeleteMenuModal();
        closeToggleStatusModal();
    }
});

// Form validation for create menu
document.getElementById('createMenuForm').addEventListener('submit', function(e) {
    const name = document.getElementById('menu_name').value.trim();
    const location = document.getElementById('menu_location').value;
    
    if (!name) {
        alert('Please enter a menu name');
        e.preventDefault();
        return;
    }
    
    if (!location) {
        alert('Please select a menu location');
        e.preventDefault();
        return;
    }
});

// Auto-focus menu name field when modal opens
function createMenu() {
    document.getElementById('createMenuModal').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('menu_name').focus();
    }, 100);
}
</script>
