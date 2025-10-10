<?php $this->layout('admin') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Autosave Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Autosaves</li>
    </ol>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/admin/autosaves" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= $this->escape($search) ?>" 
                           placeholder="Search by title or content...">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="/admin/autosaves" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Autosaves Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-save me-1"></i>
                Autosaved Content (<?= $totalItems ?> total)
            </span>
            <?php if (!empty($autosaves)): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteSelected()">
                <i class="fas fa-trash me-1"></i>
                Delete Selected
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($autosaves)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-save text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Autosaves Found</h5>
                    <p class="text-muted">You don't have any autosaved content yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th style="width: 25%; max-width: 250px;">Title</th>
                                <th style="width: 100px;">Type</th>
                                <th style="width: 30%;">Content Preview</th>
                                <th style="width: 140px;">Last Saved</th>
                                <th style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autosaves as $autosave): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="autosave-checkbox" value="<?= $autosave['id'] ?>">
                                </td>
                                <td style="word-wrap: break-word; word-break: break-word; max-width: 250px;">
                                    <strong><?= $this->escape($autosave['title']) ?></strong>
                                    <?php if ($autosave['content_id']): ?>
                                        <br><small class="text-muted">Editing content #<?= $autosave['content_id'] ?></small>
                                    <?php else: ?>
                                        <br><small class="text-success">New content</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $autosave['type'] === 'article' ? 'primary' : 'info' ?>">
                                        <?= ucfirst($autosave['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $preview = strip_tags($autosave['content'] ?? '');
                                    $preview = substr($preview, 0, 100);
                                    if (strlen($preview) >= 100) $preview .= '...';
                                    ?>
                                    <small class="text-muted"><?= $this->escape($preview ?: 'No content') ?></small>
                                </td>
                                <td>
                                    <small>
                                        <?= date('M j, Y g:i A', strtotime($autosave['updated_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($autosave['content_id']): ?>
                                        <a href="/admin/content/<?= $autosave['content_id'] ?>/edit" 
                                           class="btn btn-sm btn-outline-primary" title="Continue editing">
                                            <i class="fas fa-edit"></i> Continue
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="continueNewContent('<?= $autosave['master_content_uuid'] ?>')" 
                                                title="Continue as new content">
                                            <i class="fas fa-plus"></i> Continue
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteAutosave(<?= $autosave['id'] ?>)" title="Delete autosave">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Autosaves pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                Previous
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                Next
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.autosave-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function deleteAutosave(id) {
    if (!confirm('Are you sure you want to delete this autosave? This action cannot be undone.')) {
        return;
    }

    fetch(`/admin/autosaves/${id}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=<?= $csrf_token ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete autosave'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the autosave');
    });
}

function bulkDeleteSelected() {
    const checkboxes = document.querySelectorAll('.autosave-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one autosave to delete.');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${checkboxes.length} autosave(s)? This action cannot be undone.`)) {
        return;
    }

    const deletePromises = Array.from(checkboxes).map(checkbox => {
        return fetch(`/admin/autosaves/${checkbox.value}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `_token=<?= $csrf_token ?>`
        });
    });

    Promise.all(deletePromises)
        .then(() => {
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting autosaves');
        });
}

function continueNewContent(masterUuid) {
    // Redirect to create page with master UUID to continue autosaved content
    window.location.href = `/admin/content/create?master_uuid=${encodeURIComponent(masterUuid)}`;
}
</script>