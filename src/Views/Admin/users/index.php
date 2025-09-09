<?php
/**
 * User Management - Index View (Fixed)
 * Lists all users with search, pagination and bulk operations.
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">User Management</h2>
        <a href="/admin/users/create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New User
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="px-6 py-4 border-b border-gray-200">
        <form method="GET" action="/admin/users" class="flex space-x-4 items-end">
            <div class="flex-grow">
                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                <input type="text" name="search" id="search" value="<?= $this->escape($filters['search'] ?? '') ?>" placeholder="Search by username or email..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
            </div>
            <div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700">Filter</button>
                <a href="/admin/users" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Clear</a>
            </div>
        </form>
    </div>

    <!-- User List -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900"><?= $this->escape($user['username']) ?></div>
                                <?php if ($user['user_id'] === $current_user_id): ?>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">You</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-500"><?= $this->escape($user['email']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="/admin/users/<?= $user['user_id'] ?>/edit" class="text-blue-600 hover:text-blue-900">Edit</a>
                            <?php if ($user['user_id'] !== $current_user_id): ?>
                                <form action="/admin/users/<?= $user['user_id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-12">
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                            <p class="mt-1 text-sm text-gray-500">No users matched your search criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="bg-white px-6 py-3 border-t border-gray-200">
        <?php 
            // **THE DEFINITIVE FIX:**
            // Pre-build the base URL with the correct filters before passing it to the partial.
            // This simplifies the partial and removes the source of the fatal error.
            $baseUrl = '/admin/users?' . http_build_query(array_filter($filters));
            echo $this->render('public/partials/pagination', [
                'current_page' => $pagination['current_page'],
                'total_pages'  => $pagination['total_pages'],
                'base_url'     => $baseUrl
            ]); 
        ?>
    </div>
    <?php endif; ?>
</div>