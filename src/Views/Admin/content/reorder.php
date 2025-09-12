<?php
/**
 * Content Management - Reorder View
 * Drag-and-drop interface for reordering content
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Reorder Content</h2>
            <a href="/admin/content" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Back to Content
            </a>
        </div>
    </div>

    <!-- Filter by Content Type -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center space-x-4">
            <label for="type_filter" class="block text-sm font-medium text-gray-700">Filter by type:</label>
            <select name="type_filter" id="type_filter" onchange="filterContent(this.value)" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">All Content</option>
                <option value="article" <?= $content_type === 'article' ? 'selected' : '' ?>>Articles Only</option>
                <option value="photobook" <?= $content_type === 'photobook' ? 'selected' : '' ?>>Photobooks Only</option>
            </select>
            <div class="flex-1"></div>
            <div class="text-sm text-gray-500">
                <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                    Drag items to reorder them
                </span>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Save Status -->
        <div id="save-status" class="mb-4 p-3 rounded hidden">
            <span id="save-message"></span>
        </div>

        <?php if (!empty($content)): ?>
        <!-- Sortable Content List -->
        <div id="sortable-content" class="space-y-2">
            <?php foreach ($content as $item): ?>
            <div class="sortable-item bg-gray-50 p-4 rounded-lg border border-gray-200 hover:bg-gray-100 cursor-move transition-colors"
                 data-id="<?= $item['content_id'] ?>" data-type="<?= $item['content_type'] ?>">
                <div class="flex items-center space-x-4">
                    <!-- Drag Handle -->
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM7 8a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM7 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 8a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"></path>
                        </svg>
                    </div>

                    <!-- Content Info -->
                    <div class="flex-1 flex items-center space-x-4">
                        <!-- Image -->
                        <div class="flex-shrink-0 w-16">
                            <?php if ($item['teaser_image']): ?>
                            <img class="admin-image-43 rounded" src="/uploads/<?= $this->escape($item['teaser_image']) ?>" alt="<?= $this->escape($item['title']) ?>">
                            <?php else: ?>
                            <div class="admin-image-43 bg-gray-300 rounded flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <?php if ($item['content_type'] === 'photobook'): ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    <?php else: ?>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    <?php endif; ?>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Title and Type -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                    <?= $this->escape($item['title']) ?>
                                </h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $item['content_type'] === 'photobook' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' ?>">
                                    <?= ucfirst($item['content_type']) ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $item['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>
                            </div>
                            <?php if ($item['teaser']): ?>
                            <p class="text-sm text-gray-500 truncate">
                                <?= $this->escape(substr(strip_tags($item['teaser']), 0, 100)) ?>...
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Current Order -->
                        <div class="flex-shrink-0 text-sm text-gray-500">
                            <span class="bg-gray-200 px-2 py-1 rounded">
                                #<?= $item['sort_order'] ?>
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex-shrink-0 flex items-center space-x-2">
                            <a href="/admin/content/<?= $item['content_id'] ?>/edit" class="text-blue-600 hover:text-blue-900 text-sm">
                                Edit
                            </a>
                            <a href="/<?= $item['content_type'] ?>s/<?= $item['url_alias'] ?>" target="_blank" class="text-green-600 hover:text-green-900 text-sm">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Save Changes Button -->
        <div class="mt-6 flex justify-center">
            <button type="button" onclick="saveOrder()" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save New Order
            </button>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.6.713-3.714m0 0A9.971 9.971 0 0124 34c4.75 0 8.971 2.99 10.287 7.286"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No content to reorder</h3>
            <p class="mt-1 text-sm text-gray-500">Create some content first to be able to reorder it.</p>
            <div class="mt-6">
                <a href="/admin/content/create?type=article" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Content
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
let sortable;
let hasUnsavedChanges = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

function initializeSortable() {
    const sortableElement = document.getElementById('sortable-content');
    
    if (sortableElement && typeof Sortable !== 'undefined') {
        sortable = Sortable.create(sortableElement, {
            handle: '.sortable-item',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                hasUnsavedChanges = true;
                updatePositions();
                showUnsavedChanges();
            }
        });
    }
}

function updatePositions() {
    const items = document.querySelectorAll('.sortable-item');
    items.forEach((item, index) => {
        const orderSpan = item.querySelector('.bg-gray-200');
        if (orderSpan) {
            orderSpan.textContent = '#' + (index + 1);
        }
    });
}

function showUnsavedChanges() {
    const status = document.getElementById('save-status');
    const message = document.getElementById('save-message');
    
    status.className = 'mb-4 p-3 rounded bg-yellow-100 border border-yellow-200';
    message.textContent = 'You have unsaved changes. Click "Save New Order" to apply them.';
    status.classList.remove('hidden');
}

function filterContent(type) {
    const url = new URL(window.location);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    window.location = url.toString();
}

function saveOrder() {
    const items = document.querySelectorAll('.sortable-item');
    const orderData = [];
    
    items.forEach((item, index) => {
        orderData.push({
            id: parseInt(item.dataset.id),
            position: index + 1
        });
    });
    
    const formData = new FormData();
    formData.append('order', JSON.stringify(orderData));
    formData.append('csrf_token', '<?= $csrf_token ?>');
    
    // Disable save button during request
    const saveButton = event.target;
    const originalText = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...';
    
    fetch('/admin/content/update-order', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSaveStatus('success', data.message || 'Order saved successfully');
            hasUnsavedChanges = false;
        } else {
            showSaveStatus('error', data.message || 'Failed to save order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showSaveStatus('error', 'An error occurred while saving the order');
    })
    .finally(() => {
        // Re-enable save button
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
    });
}

function showSaveStatus(type, message) {
    const status = document.getElementById('save-status');
    const messageSpan = document.getElementById('save-message');
    
    if (type === 'success') {
        status.className = 'mb-4 p-3 rounded bg-green-100 border border-green-200 text-green-800';
    } else {
        status.className = 'mb-4 p-3 rounded bg-red-100 border border-red-200 text-red-800';
    }
    
    messageSpan.textContent = message;
    status.classList.remove('hidden');
    
    // Auto-hide success messages after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            status.classList.add('hidden');
        }, 3000);
    }
}

// Warn about unsaved changes when leaving
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (hasUnsavedChanges) {
            saveOrder();
        }
    }
});

// Add custom styles for sortable
const style = document.createElement('style');
style.textContent = `
    .sortable-ghost {
        opacity: 0.4;
        background: #f3f4f6;
        border: 2px dashed #d1d5db;
    }
    
    .sortable-chosen {
        transform: scale(1.02);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .sortable-drag {
        transform: rotate(5deg);
        opacity: 0.8;
    }
    
    .sortable-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
`;
document.head.appendChild(style);
</script>
