<?php
/**
 * User Management - Edit View
 * Form for editing existing users with statistics
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Edit User: <?= $this->escape($user->getAttribute('username')) ?></h2>
            <div class="flex space-x-2">
                <a href="/admin/users" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                    </svg>
                    Back to Users
                </a>
                <?php if (!$is_current_user): ?>
                <button onclick="deleteUser(<?= $user->getId() ?>, '<?= $this->escape($user->getAttribute('username')) ?>')" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete User
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <form method="POST" action="/admin/users/<?= $user->getId() ?>/update" id="userForm">
                <?= $this->csrfField() ?>

                <!-- User Information -->
                <div class="space-y-6 mb-6">
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="username" id="username" required maxlength="50"
                                   value="<?= $this->escape($form_data['username'] ?? $user->getAttribute('username')) ?>"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['username']) ? 'border-red-300' : '' ?>"
                                   onblur="checkUsername()">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center" id="username-status" style="display: none;">
                                <!-- Status icons will be inserted here -->
                            </div>
                        </div>
                        <?php if (isset($form_errors['username'])): ?>
                        <p class="mt-1 text-sm text-red-600" id="username-error"><?= $this->escape($form_errors['username']) ?></p>
                        <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">Username must be 3-50 characters, letters, numbers, and underscores only</p>
                        <p class="mt-1 text-sm" id="username-message" style="display: none;"></p>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="email" name="email" id="email" required maxlength="255"
                                   value="<?= $this->escape($form_data['email'] ?? $user->getAttribute('email')) ?>"
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['email']) ? 'border-red-300' : '' ?>"
                                   onblur="checkEmail()">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center" id="email-status" style="display: none;">
                                <!-- Status icons will be inserted here -->
                            </div>
                        </div>
                        <?php if (isset($form_errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600" id="email-error"><?= $this->escape($form_errors['email']) ?></p>
                        <?php else: ?>
                        <p class="mt-1 text-sm text-gray-500">Valid email address required</p>
                        <p class="mt-1 text-sm" id="email-message" style="display: none;"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="border-t pt-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                        <button type="button" onclick="togglePasswordChange()" id="password-toggle-btn" class="text-sm text-blue-600 hover:text-blue-500">
                            Change Password
                        </button>
                    </div>
                    
                    <div id="password-change-section" style="display: none;">
                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <div class="relative">
                                    <input type="password" name="password" id="password" minlength="8" maxlength="255"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($form_errors['password']) ? 'border-red-300' : '' ?>"
                                           oninput="checkPasswordStrength()">
                                    <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="password-eye">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <?php if (isset($form_errors['password'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= $this->escape($form_errors['password']) ?></p>
                                <?php else: ?>
                                <p class="mt-1 text-sm text-gray-500">Leave empty to keep current password. Minimum 8 characters if changing.</p>
                                <?php endif; ?>
                                
                                <!-- Password Strength Indicator -->
                                <div class="mt-2" id="password-strength" style="display: none;">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div id="password-strength-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%;"></div>
                                        </div>
                                        <span id="password-strength-text" class="text-xs text-gray-500"></span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm New Password
                                </label>
                                <div class="relative">
                                    <input type="password" name="confirm_password" id="confirm_password"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           oninput="checkPasswordMatch()">
                                    <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="confirm_password-eye">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-sm" id="password-match-message"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="border-t pt-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">User ID:</span>
                            <span class="text-gray-600">#<?= $user->getId() ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Account Created:</span>
                            <span class="text-gray-600"><?= date('M j, Y g:i A', strtotime($user->getAttribute('created_at'))) ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Last Updated:</span>
                            <span class="text-gray-600"><?= date('M j, Y g:i A', strtotime($user->getAttribute('updated_at'))) ?></span>
                        </div>
                        <?php if ($is_current_user): ?>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Current User
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="/admin/users" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update User
                    </button>
                </div>
            </form>
        </div>

        <!-- User Statistics Sidebar -->
        <div class="space-y-6">
            <!-- Content Statistics -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Content Statistics</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Total Content:</span>
                        <span class="text-sm font-semibold text-gray-900"><?= $user_stats['total_content'] ?? 0 ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Published:</span>
                        <span class="text-sm font-semibold text-green-600"><?= $user_stats['published_content'] ?? 0 ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600">Drafts:</span>
                        <span class="text-sm font-semibold text-yellow-600"><?= $user_stats['draft_content'] ?? 0 ?></span>
                    </div>
                </div>
                
                <?php if (!empty($user_stats['recent_content'])): ?>
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Recent Content</h4>
                    <div class="space-y-2">
                        <?php foreach ($user_stats['recent_content'] as $content): ?>
                        <div class="text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-800 truncate" title="<?= $this->escape($content['title']) ?>">
                                    <?= $this->escape(substr($content['title'], 0, 30)) ?><?= strlen($content['title']) > 30 ? '...' : '' ?>
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= $content['status'] === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= ucfirst($content['status']) ?>
                                </span>
                            </div>
                            <div class="text-gray-500 mt-1">
                                <?= date('M j, Y', strtotime($content['updated_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="/admin/content?search=<?= urlencode($user->getAttribute('username')) ?>" class="text-xs text-blue-600 hover:text-blue-500">
                            View all content by this user →
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="/admin/content?user_id=<?= $user->getId() ?>" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        View User's Content
                    </a>
                    <?php if (!$is_current_user): ?>
                    <button onclick="resetPassword()" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        Send Password Reset
                    </button>
                    <?php endif; ?>
                    <a href="mailto:<?= $this->escape($user->getAttribute('email')) ?>" class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        Send Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2">Delete User</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete user "<span id="deleteUsername"></span>"? This action cannot be undone.
                </p>
                <p class="text-xs text-red-600 mt-2">
                    Note: Users with associated content cannot be deleted.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST" action="" class="inline">
                    <?= $this->csrfField() ?>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 mr-2">
                        Delete User
                    </button>
                </form>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const userId = <?= $user->getId() ?>;
let usernameTimeout, emailTimeout;

// Toggle password change section
function togglePasswordChange() {
    const section = document.getElementById('password-change-section');
    const btn = document.getElementById('password-toggle-btn');
    
    if (section.style.display === 'none') {
        section.style.display = 'block';
        btn.textContent = 'Cancel Password Change';
        btn.className = 'text-sm text-red-600 hover:text-red-500';
    } else {
        section.style.display = 'none';
        btn.textContent = 'Change Password';
        btn.className = 'text-sm text-blue-600 hover:text-blue-500';
        
        // Clear password fields
        document.getElementById('password').value = '';
        document.getElementById('confirm_password').value = '';
        document.getElementById('password-strength').style.display = 'none';
        document.getElementById('password-match-message').textContent = '';
    }
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(inputId + '-eye');
    
    if (input.type === 'password') {
        input.type = 'text';
        eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L18 18"></path>
        `;
    } else {
        input.type = 'password';
        eye.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
    }
}

// Check username availability
function checkUsername() {
    const username = document.getElementById('username').value.trim();
    const currentUsername = '<?= $this->escape($user->getAttribute('username')) ?>';
    
    if (username.length < 3 || username === currentUsername) {
        hideStatus('username');
        return;
    }
    
    clearTimeout(usernameTimeout);
    usernameTimeout = setTimeout(() => {
        showLoading('username');
        
        fetch('/admin/users/check-username?' + new URLSearchParams({
            username: username,
            exclude_id: userId
        }))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus('username', data.available, data.message);
            } else {
                showStatus('username', false, data.message);
            }
        })
        .catch(error => {
            console.error('Error checking username:', error);
            hideStatus('username');
        });
    }, 500);
}

// Check email availability
function checkEmail() {
    const email = document.getElementById('email').value.trim();
    const currentEmail = '<?= $this->escape($user->getAttribute('email')) ?>';
    
    if (!email || !isValidEmail(email) || email === currentEmail) {
        hideStatus('email');
        return;
    }
    
    clearTimeout(emailTimeout);
    emailTimeout = setTimeout(() => {
        showLoading('email');
        
        fetch('/admin/users/check-email?' + new URLSearchParams({
            email: email,
            exclude_id: userId
        }))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus('email', data.available, data.message);
            } else {
                showStatus('email', false, data.message);
            }
        })
        .catch(error => {
            console.error('Error checking email:', error);
            hideStatus('email');
        });
    }, 500);
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showLoading(field) {
    const status = document.getElementById(field + '-status');
    status.innerHTML = '<div class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-blue-600 rounded-full"></div>';
    status.style.display = 'flex';
}

function showStatus(field, available, message) {
    const status = document.getElementById(field + '-status');
    const messageEl = document.getElementById(field + '-message');
    
    if (available) {
        status.innerHTML = '<svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
        messageEl.className = 'mt-1 text-sm text-green-600';
    } else {
        status.innerHTML = '<svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>';
        messageEl.className = 'mt-1 text-sm text-red-600';
    }
    
    status.style.display = 'flex';
    messageEl.textContent = message;
    messageEl.style.display = 'block';
}

function hideStatus(field) {
    document.getElementById(field + '-status').style.display = 'none';
    document.getElementById(field + '-message').style.display = 'none';
}

// Password strength checker
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthIndicator = document.getElementById('password-strength');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    if (password.length === 0) {
        strengthIndicator.style.display = 'none';
        return;
    }
    
    strengthIndicator.style.display = 'block';
    
    let score = 0;
    
    if (password.length >= 8) score += 25;
    if (/[A-Z]/.test(password)) score += 25;
    if (/[a-z]/.test(password)) score += 25;
    if (/[0-9]/.test(password)) score += 25;
    
    strengthBar.style.width = score + '%';
    
    if (score < 50) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-red-500';
        strengthText.textContent = 'Weak';
        strengthText.className = 'text-xs text-red-600';
    } else if (score < 75) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-yellow-500';
        strengthText.textContent = 'Fair';
        strengthText.className = 'text-xs text-yellow-600';
    } else if (score < 100) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-blue-500';
        strengthText.textContent = 'Good';
        strengthText.className = 'text-xs text-blue-600';
    } else {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300 bg-green-500';
        strengthText.textContent = 'Strong';
        strengthText.className = 'text-xs text-green-600';
    }
    
    checkPasswordMatch();
}

// Password match checker
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const message = document.getElementById('password-match-message');
    
    if (confirmPassword === '') {
        message.textContent = '';
        message.className = 'mt-1 text-sm';
        return;
    }
    
    if (password === confirmPassword) {
        message.textContent = 'Passwords match';
        message.className = 'mt-1 text-sm text-green-600';
    } else {
        message.textContent = 'Passwords do not match';
        message.className = 'mt-1 text-sm text-red-600';
    }
}

// User deletion
function deleteUser(userId, username) {
    document.getElementById('deleteUsername').textContent = username;
    document.getElementById('deleteForm').action = '/admin/users/' + userId + '/delete';
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Quick actions
function resetPassword() {
    if (confirm('Send a password reset email to this user?')) {
        // Implementation would depend on your password reset system
        alert('Password reset functionality would be implemented here');
    }
}

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (!username || username.length < 3) {
        alert('Username must be at least 3 characters long');
        e.preventDefault();
        return;
    }
    
    if (!email || !isValidEmail(email)) {
        alert('Please enter a valid email address');
        e.preventDefault();
        return;
    }
    
    // Only validate password if it's being changed
    if (password || confirmPassword) {
        if (!password || password.length < 8) {
            alert('Password must be at least 8 characters long');
            e.preventDefault();
            return;
        }
        
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            e.preventDefault();
            return;
        }
    }
});

// Close modal on backdrop click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
