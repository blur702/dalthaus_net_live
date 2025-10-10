<?php
/**
 * @var array $uploads
 * @var array $stats
 * @var int $currentPage
 * @var string|null $uploadType
 * @var bool $showUnused
 * @var string $csrf_token
 */
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Media Uploads</h1>
        <div class="flex gap-2">
            <a href="/admin/media/stats" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                View Statistics
            </a>
            <form method="POST" action="/admin/media/cleanup-orphaned" class="inline">
                <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
                <button type="submit" onclick="return confirm('Clean up orphaned upload records?')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Cleanup Orphaned
                </button>
            </form>
        </div>
    </div>

    <!-- Statistics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm text-gray-600">Total Uploads</div>
            <div class="text-2xl font-bold text-gray-800"><?= $stats['totals']['total_uploads'] ?? 0 ?></div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm text-gray-600">Total Size</div>
            <div class="text-2xl font-bold text-gray-800">
                <?= round(($stats['totals']['total_size'] ?? 0) / 1024 / 1024, 2) ?> MB
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm text-gray-600">Used in Content</div>
            <div class="text-2xl font-bold text-green-600"><?= $stats['totals']['used_uploads'] ?? 0 ?></div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm text-gray-600">Unused</div>
            <div class="text-2xl font-bold text-orange-600"><?= $stats['totals']['unused_uploads'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex gap-2">
                <a href="/admin/media" class="px-3 py-1 rounded <?= !$uploadType && !$showUnused ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                    All
                </a>
                <a href="/admin/media?type=tinymce" class="px-3 py-1 rounded <?= $uploadType === 'tinymce' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                    TinyMCE
                </a>
                <a href="/admin/media?type=dual_display" class="px-3 py-1 rounded <?= $uploadType === 'dual_display' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                    Display Images
                </a>
                <a href="/admin/media?type=dual_modal" class="px-3 py-1 rounded <?= $uploadType === 'dual_modal' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                    Modal Images
                </a>
                <a href="/admin/media?unused=1" class="px-3 py-1 rounded <?= $showUnused ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700' ?>">
                    Unused Only
                </a>
            </div>
        </div>
    </div>

    <!-- Uploads Grid -->
    <?php if (empty($uploads)): ?>
        <div class="bg-white p-8 rounded-lg shadow text-center">
            <p class="text-gray-600">No uploads found.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preview</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($uploads as $upload): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="<?= $this->escape($upload['filepath']) ?>" target="_blank" class="block">
                                    <img src="<?= $this->escape($upload['filepath']) ?>"
                                         alt="<?= $this->escape($upload['original_filename'] ?? 'Upload') ?>"
                                         class="h-16 w-16 object-cover rounded"
                                         onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22><rect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/><text x=%2250%%22 y=%2250%%22 font-size=%2214%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23666%22>No Preview</text></svg>'">
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 break-words max-w-xs">
                                    <?= $this->escape($upload['original_filename'] ?? $upload['filename']) ?>
                                </div>
                                <div class="text-xs text-gray-500 break-all">
                                    <?= $this->escape($upload['filename']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?= $upload['upload_type'] === 'tinymce' ? 'bg-blue-100 text-blue-800' :
                                        ($upload['upload_type'] === 'dual_display' ? 'bg-green-100 text-green-800' :
                                        'bg-purple-100 text-purple-800') ?>">
                                    <?= $this->escape($upload['upload_type']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= round(($upload['file_size'] ?? 0) / 1024, 2) ?> KB
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?= $this->escape($upload['display_name'] ?? $upload['username'] ?? 'Unknown') ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($upload['used_in_content']): ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Used</span>
                                    <?php if ($upload['content_title']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            in: <?= $this->escape($upload['content_title']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Unused</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('M j, Y', strtotime($upload['created_at'])) ?>
                                <div class="text-xs text-gray-400">
                                    <?= date('g:i A', strtotime($upload['created_at'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex gap-2">
                                    <a href="/admin/media/<?= $upload['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                        View
                                    </a>
                                    <button onclick="deleteUpload(<?= $upload['id'] ?>, '<?= $this->escape($csrf_token) ?>')"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
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
            location.reload();
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
