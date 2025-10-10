<?php
/**
 * @var array $upload
 * @var string $csrf_token
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="/admin/media" class="text-blue-600 hover:text-blue-800">&larr; Back to Media Uploads</a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800">Upload Details</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
            <!-- Image Preview -->
            <div class="space-y-4">
                <div class="border-2 border-gray-200 rounded-lg p-4">
                    <img src="<?= $this->escape($upload['filepath']) ?>"
                         alt="<?= $this->escape($upload['original_filename'] ?? 'Upload') ?>"
                         class="max-w-full h-auto rounded"
                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22><rect fill=%22%23f3f4f6%22 width=%22400%22 height=%22300%22/><text x=%2250%%22 y=%2250%%22 font-size=%2220%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22>Image not found</text></svg>'">
                </div>

                <div class="flex gap-2">
                    <a href="<?= $this->escape($upload['filepath']) ?>"
                       target="_blank"
                       class="flex-1 px-4 py-2 bg-blue-600 text-white text-center rounded hover:bg-blue-700">
                        Open in New Tab
                    </a>
                    <button onclick="copyPath('<?= $this->escape($upload['filepath']) ?>')"
                            class="flex-1 px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        Copy Path
                    </button>
                </div>
            </div>

            <!-- Upload Information -->
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Upload Information</h2>

                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Original Filename</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-words">
                                <?= $this->escape($upload['original_filename'] ?? 'N/A') ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Stored Filename</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all font-mono">
                                <?= $this->escape($upload['filename']) ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">File Path</dt>
                            <dd class="mt-1 text-sm text-gray-900 break-all font-mono">
                                <?= $this->escape($upload['filepath']) ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">File Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= strtoupper($this->escape($upload['file_type'] ?? 'Unknown')) ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">File Size</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= round(($upload['file_size'] ?? 0) / 1024, 2) ?> KB
                                <span class="text-gray-500">(<?= number_format($upload['file_size'] ?? 0) ?> bytes)</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Upload Type</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?= $upload['upload_type'] === 'tinymce' ? 'bg-blue-100 text-blue-800' :
                                        ($upload['upload_type'] === 'dual_display' ? 'bg-green-100 text-green-800' :
                                        'bg-purple-100 text-purple-800') ?>">
                                    <?= $this->escape($upload['upload_type']) ?>
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= $this->escape($upload['display_name'] ?? $upload['username'] ?? 'Unknown') ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Upload Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= date('F j, Y', strtotime($upload['created_at'])) ?> at
                                <?= date('g:i A', strtotime($upload['created_at'])) ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= date('F j, Y', strtotime($upload['updated_at'])) ?> at
                                <?= date('g:i A', strtotime($upload['updated_at'])) ?>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Usage Status</dt>
                            <dd class="mt-1">
                                <?php if ($upload['used_in_content']): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        Used in Content
                                    </span>
                                    <?php if ($upload['content_title']): ?>
                                        <div class="mt-2 text-sm text-gray-600">
                                            Content: <a href="/admin/content/<?= $upload['content_id'] ?>/edit"
                                                       class="text-blue-600 hover:text-blue-800">
                                                <?= $this->escape($upload['content_title']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                        Not used in any content
                                    </span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Actions</h3>
                    <div class="space-y-2">
                        <?php if (!$upload['used_in_content']): ?>
                            <button onclick="markAsUsed(<?= $upload['id'] ?>, '<?= $this->escape($csrf_token) ?>')"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Mark as Used
                            </button>
                        <?php endif; ?>

                        <button onclick="deleteUpload(<?= $upload['id'] ?>, '<?= $this->escape($csrf_token) ?>')"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                            Delete Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyPath(path) {
    navigator.clipboard.writeText(path).then(() => {
        alert('Path copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Failed to copy path to clipboard');
    });
}

function markAsUsed(id, csrfToken) {
    if (!confirm('Mark this upload as used in content?')) {
        return;
    }

    fetch(`/admin/media/${id}/mark-used`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            _token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred.');
    });
}

function deleteUpload(id, csrfToken) {
    if (!confirm('Are you sure you want to delete this upload? This will also delete the physical file.')) {
        return;
    }

    fetch(`/admin/media/${id}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            _token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '/admin/media';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the upload.');
    });
}
</script>
