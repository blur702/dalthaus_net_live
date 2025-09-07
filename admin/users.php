<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Utils/Auth.php';
require_once __DIR__ . '/../src/Models/User.php';
require_once __DIR__ . '/../src/Utils/Security.php';

use CMS\Utils\Auth;
use CMS\Models\User;
use CMS\Utils\Security;

// Check authentication
Auth::requireLogin();

// Get configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize variables
$error = '';
$success = '';
$page = (int) ($_GET['page'] ?? 1);
$itemsPerPage = $config['app']['items_per_page'];
$offset = ($page - 1) * $itemsPerPage;

// Process filters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'sort_by' => $_GET['sort_by'] ?? 'created_at',
    'sort_dir' => $_GET['sort_dir'] ?? 'DESC'
];

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int) ($_POST['user_id'] ?? 0);

        switch ($action) {
            case 'create_user':
                $userData = [
                    'username' => trim($_POST['username'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'password' => $_POST['password'] ?? ''
                ];

                $validationErrors = User::validateUserData($userData);
                
                if (empty($validationErrors)) {
                    try {
                        $newUser = User::createUser($userData);
                        $success = 'User created successfully.';
                    } catch (Exception $e) {
                        $error = 'Failed to create user: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Please correct the following errors: ' . implode(', ', $validationErrors);
                }
                break;

            case 'delete_user':
                if ($userId > 0) {
                    // Prevent deleting current user
                    if ($userId === Auth::getUserId()) {
                        $error = 'You cannot delete your own account.';
                    } else {
                        $user = User::find($userId);
                        if ($user) {
                            // Check if user has content
                            if ($user->hasContent()) {
                                $error = 'Cannot delete user with existing content. Please reassign or delete their content first.';
                            } else {
                                if ($user->delete()) {
                                    $success = 'User deleted successfully.';
                                } else {
                                    $error = 'Failed to delete user.';
                                }
                            }
                        } else {
                            $error = 'User not found.';
                        }
                    }
                } else {
                    $error = 'Invalid user ID.';
                }
                break;

            case 'bulk_delete':
                $selectedIds = $_POST['selected_ids'] ?? [];
                $currentUserId = Auth::getUserId();
                
                if (!empty($selectedIds) && is_array($selectedIds)) {
                    $deletedCount = 0;
                    $errors = [];
                    
                    foreach ($selectedIds as $id) {
                        $id = (int) $id;
                        
                        // Skip current user
                        if ($id === $currentUserId) {
                            $errors[] = "Skipped your own account (ID: $id)";
                            continue;
                        }
                        
                        $user = User::find($id);
                        if ($user) {
                            if ($user->hasContent()) {
                                $errors[] = "Skipped user '{$user->username}' (has content)";
                            } else {
                                if ($user->delete()) {
                                    $deletedCount++;
                                } else {
                                    $errors[] = "Failed to delete user '{$user->username}'";
                                }
                            }
                        }
                    }
                    
                    if ($deletedCount > 0) {
                        $success = "Successfully deleted {$deletedCount} user(s).";
                        if (!empty($errors)) {
                            $success .= ' ' . implode(' ', $errors);
                        }
                    } else {
                        $error = empty($errors) ? 'No users were deleted.' : implode(' ', $errors);
                    }
                } else {
                    $error = 'No users selected.';
                }
                break;
        }
    }
}

// Get users data
$usersList = User::getForAdmin($filters, $itemsPerPage, $offset);
$totalCount = User::countForAdmin($filters);
$totalPages = (int) ceil($totalCount / $itemsPerPage);

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Admin Header -->
    <?php include 'includes/header.php'; ?>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    User Management
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage user accounts and permissions
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <button type="button" onclick="openCreateUserModal()" 
                        class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                    Add New User
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

        <!-- Search and Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search Users</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                               placeholder="Username or email..." 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="sort_by" class="block text-sm font-medium text-gray-700">Sort By</label>
                        <select name="sort_by" id="sort_by" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="created_at" <?= $filters['sort_by'] === 'created_at' ? 'selected' : '' ?>>Created Date</option>
                            <option value="username" <?= $filters['sort_by'] === 'username' ? 'selected' : '' ?>>Username</option>
                            <option value="email" <?= $filters['sort_by'] === 'email' ? 'selected' : '' ?>>Email</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-search -ml-1 mr-2 h-4 w-4"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <?php if (!empty($usersList)): ?>
                <form method="POST" id="bulk-form">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                    
                    <!-- Bulk Actions -->
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="select-all" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="select-all" class="ml-2 text-sm text-gray-900">Select All</label>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <button type="submit" name="action" value="bulk_delete" id="bulk-delete" 
                                        class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        disabled onclick="return confirm('Are you sure you want to delete the selected users?')">
                                    <i class="fas fa-trash -ml-1 mr-2 h-4 w-4"></i>
                                    Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($usersList as $user): ?>
                            <li class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <?php if ($user->user_id !== Auth::getUserId()): ?>
                                            <input type="checkbox" name="selected_ids[]" value="<?= $user->user_id ?>" 
                                                   class="user-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <?php else: ?>
                                            <div class="w-4 h-4"></div> <!-- Placeholder to maintain alignment -->
                                        <?php endif; ?>
                                        
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-white">
                                                        <?= strtoupper(substr($user->username, 0, 1)) ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="ml-4">
                                                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                                        <?= htmlspecialchars($user->username) ?>
                                                        <?php if ($user->user_id === Auth::getUserId()): ?>
                                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                You
                                                            </span>
                                                        <?php endif; ?>
                                                    </h3>
                                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($user->email) ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-2 flex items-center text-sm text-gray-500 space-x-4">
                                                <span class="flex items-center">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    Joined <?= $user->getFormattedCreatedDate() ?>
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-file-text mr-1"></i>
                                                    <?= $user->content_count ?? 0 ?> content items
                                                </span>
                                                <span class="flex items-center">
                                                    <i class="fas fa-eye mr-1"></i>
                                                    <?= $user->published_count ?? 0 ?> published
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <?php if ($user->user_id !== Auth::getUserId()): ?>
                                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $user->user_id ?>">
                                                <button type="submit" 
                                                        class="inline-flex items-center p-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                                        <?= $user->hasContent() ? 'title="Cannot delete user with content"' : '' ?>
                                                        <?= $user->hasContent() ? 'disabled' : '' ?>>
                                                    <i class="fas fa-trash h-4 w-4"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </form>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-gray-400 text-5xl mb-4"></i>
                    <p class="text-lg font-medium text-gray-900">No users found</p>
                    <p class="text-gray-500">Get started by creating a new user account.</p>
                    <div class="mt-6">
                        <button type="button" onclick="openCreateUserModal()"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus -ml-1 mr-2 h-4 w-4"></i>
                            Add New User
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-6">
                <div class="hidden sm:block">
                    <p class="text-sm text-gray-700">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalCount) ?> of <?= $totalCount ?> results
                    </p>
                </div>
                
                <div class="flex-1 flex justify-between sm:justify-end">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Create User Modal -->
    <div id="create-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                    <button type="button" onclick="closeCreateUserModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" id="create-user-form">
                    <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="modal-username" class="block text-sm font-medium text-gray-700">Username *</label>
                            <input type="text" name="username" id="modal-username" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="modal-email" class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="email" id="modal-email" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="modal-password" class="block text-sm font-medium text-gray-700">Password *</label>
                            <input type="password" name="password" id="modal-password" required minlength="8"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500">Minimum 8 characters</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateUserModal()" 
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Modal functions
    function openCreateUserModal() {
        document.getElementById('create-user-modal').classList.remove('hidden');
        document.getElementById('modal-username').focus();
    }

    function closeCreateUserModal() {
        document.getElementById('create-user-modal').classList.add('hidden');
        document.getElementById('create-user-form').reset();
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateUserModal();
        }
    });

    // Select all functionality
    document.getElementById('select-all')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        toggleBulkActions();
    });

    // Individual checkbox functionality
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkActions);
    });

    // Enable/disable bulk actions
    function toggleBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
        const bulkDelete = document.getElementById('bulk-delete');
        if (bulkDelete) {
            bulkDelete.disabled = selectedCheckboxes.length === 0;
        }
    }
    </script>
</body>
</html>