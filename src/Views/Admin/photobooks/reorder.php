<?php
/**
 * Photobooks Management - Reorder View
 * Drag-and-drop interface for reordering photobooks
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Reorder Photobooks</h2>
            <a href="/admin/photobooks" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Back to Photobooks
            </a>
        </div>
    </div>

    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                <span class="inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                    Drag photobooks to reorder them
                </span>
            </div>
            <div class="text-sm text-gray-600">
                <?= count($photobooks) ?> photobook<?= count($photobooks) !== 1 ? 's' : '' ?>
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- Save Status -->
        <div id="save-status" class="mb-4 p-3 rounded hidden">
            <span id="save-message"></span>
        </div>

        <?php if (!empty($photobooks)): ?>
        <!-- Sortable Photobooks List -->
        <div id="sortable-photobooks" class="space-y-2">
            <?php foreach ($photobooks as $photobook): ?>
            <div class="sortable-item bg-gray-50 p-4 rounded-lg border border-gray-200 hover:bg-gray-100 cursor-move transition-colors"
                 data-id="<?= $photobook['content_id'] ?>">
                <div class="flex items-center space-x-4">
                    <!-- Drag Handle -->
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM7 8a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM7 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 8a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM13 14a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"></path>
                        </svg>
                    </div>

                    <!-- Photobook Info -->
                    <div class="flex-1 flex items-center space-x-4">
                        <!-- Image -->
                        <div class="flex-shrink-0 w-16">
                            <?php if ($photobook['teaser_image']): ?>
                            <img class="admin-image-43 rounded" src="<?= $this->escape($photobook['teaser_image']) ?>" alt="<?= $this->escape($photobook['title']) ?>">
                            <?php else: ?>
                            <div class="admin-image-43 bg-green-100 rounded flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Title and Status -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-sm font-medium text-gray-900 truncate">
                                    <?= $this->escape($photobook['title']) ?>
                                </h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Photobook
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $photobook['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($photobook['status']) ?>
                                </span>
                            </div>
                            <?php if ($photobook['teaser']): ?>
                            <p class="text-sm text-gray-500 truncate">
                                <?= $this->escape(substr(strip_tags($photobook['teaser']), 0, 100)) ?>...
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Current Order -->
                        <div class="flex-shrink-0 text-sm text-gray-500">
                            <span class="bg-gray-200 px-2 py-1 rounded">
                                #<?= $photobook['sort_order'] ?>
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex-shrink-0 flex items-center space-x-2">
                            <a href="/admin/content/<?= $photobook['content_id'] ?>/edit" class="text-blue-600 hover:text-blue-900 text-sm">
                                Edit
                            </a>
                            <a href="/photobook/<?= $photobook['url_alias'] ?>" target="_blank" class="text-green-600 hover:text-green-900 text-sm">
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
            <button type="button" onclick="saveOrder(event)" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No photobooks to reorder</h3>
            <p class="mt-1 text-sm text-gray-500">Create some photobooks first to be able to reorder them.</p>
            <div class="mt-6">
                <a href="/admin/content/create?type=photobook" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Photobook
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
    const sortableElement = document.getElementById('sortable-photobooks');

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

function saveOrder(event) {
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
    formData.append('_token', '<?= $csrf_token ?>');

    // Disable save button during request
    const saveButton = event ? event.target : document.querySelector('button[onclick*="saveOrder"]');
    const originalText = saveButton.innerHTML;
    saveButton.disabled = true;
    saveButton.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...';

    fetch('/admin/photobooks/update-order', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('[Photobooks Reorder] Response status:', response.status);
        console.log('[Photobooks Reorder] Response headers:', response.headers.get('content-type'));

        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Not JSON - log the HTML and throw error
            return response.text().then(html => {
                console.error('[Photobooks Reorder] Received HTML instead of JSON:', html.substring(0, 500));
                throw new Error('Server returned HTML instead of JSON (possible redirect or error page)');
            });
        }

        return response.json();
    })
    .then(data => {
        console.log('[Photobooks Reorder] Response data:', data);

        if (data.success) {
            showSaveStatus('success', data.message || 'Photobook order saved successfully');
            hasUnsavedChanges = false;
        } else {
            showSaveStatus('error', data.message || 'Failed to save photobook order');
        }
    })
    .catch(error => {
        console.error('[Photobooks Reorder] Error:', error);
        showSaveStatus('error', error.message || 'An error occurred while saving the photobook order');
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