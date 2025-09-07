<?php
/**
 * User Management - Create View
 * Form for creating new users
 */
?>

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Create New User</h2>
            <a href="/admin/users" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                </svg>
                Back to Users
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="POST" action="/admin/users/store" id="userForm">
            <?= $this->csrfField() ?>

            <!-- User Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="username" id="username" required maxlength="50"
                               value="<?= $this->escape($form_data['username'] ?? '') ?>"
                               class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['username']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>"
                               placeholder="johndoe"
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
                               value="<?= $this->escape($form_data['email'] ?? '') ?>"
                               class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out sm:text-sm <?= isset($form_errors['email']) ? 'border-red-300 bg-red-50' : 'bg-white hover:border-gray-400' ?>"
                               placeholder="john@example.com"
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

            <!-- Password -->
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password" required minlength="8" maxlength="255"
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
                <p class="mt-1 text-sm text-gray-500">Password must be at least 8 characters long</p>
                <?php endif; ?>
                
                <!-- Password Strength Indicator -->
                <div class="mt-2">
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div id="password-strength-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%;"></div>
                        </div>
                        <span id="password-strength-text" class="text-xs text-gray-500"></span>
                    </div>
                    <ul class="mt-2 text-xs text-gray-600 space-y-1" id="password-requirements">
                        <li id="req-length" class="flex items-center">
                            <span class="requirement-icon mr-2">○</span>At least 8 characters
                        </li>
                        <li id="req-uppercase" class="flex items-center">
                            <span class="requirement-icon mr-2">○</span>One uppercase letter
                        </li>
                        <li id="req-lowercase" class="flex items-center">
                            <span class="requirement-icon mr-2">○</span>One lowercase letter
                        </li>
                        <li id="req-number" class="flex items-center">
                            <span class="requirement-icon mr-2">○</span>One number
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm Password <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="confirm_password" required
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

            <!-- User Guidelines -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">User Account Guidelines</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Users will have access to the admin dashboard</li>
                                <li>They can create, edit, and manage content</li>
                                <li>Usernames and email addresses must be unique</li>
                                <li>Users should use strong, secure passwords</li>
                                <li>Account details can be modified after creation</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="/admin/users" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed" id="submit-btn">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let usernameTimeout, emailTimeout;

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
    const status = document.getElementById('username-status');
    const message = document.getElementById('username-message');
    
    if (username.length < 3) {
        hideStatus('username');
        return;
    }
    
    clearTimeout(usernameTimeout);
    usernameTimeout = setTimeout(() => {
        showLoading('username');
        
        fetch('/admin/users/check-username?' + new URLSearchParams({
            username: username
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
    const status = document.getElementById('email-status');
    const message = document.getElementById('email-message');
    
    if (!email || !isValidEmail(email)) {
        hideStatus('email');
        return;
    }
    
    clearTimeout(emailTimeout);
    emailTimeout = setTimeout(() => {
        showLoading('email');
        
        fetch('/admin/users/check-email?' + new URLSearchParams({
            email: email
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
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    let score = 0;
    let feedback = [];
    
    // Length check
    const lengthReq = document.getElementById('req-length');
    if (password.length >= 8) {
        score += 25;
        updateRequirement(lengthReq, true);
    } else {
        updateRequirement(lengthReq, false);
    }
    
    // Uppercase check
    const uppercaseReq = document.getElementById('req-uppercase');
    if (/[A-Z]/.test(password)) {
        score += 25;
        updateRequirement(uppercaseReq, true);
    } else {
        updateRequirement(uppercaseReq, false);
    }
    
    // Lowercase check
    const lowercaseReq = document.getElementById('req-lowercase');
    if (/[a-z]/.test(password)) {
        score += 25;
        updateRequirement(lowercaseReq, true);
    } else {
        updateRequirement(lowercaseReq, false);
    }
    
    // Number check
    const numberReq = document.getElementById('req-number');
    if (/[0-9]/.test(password)) {
        score += 25;
        updateRequirement(numberReq, true);
    } else {
        updateRequirement(numberReq, false);
    }
    
    // Update strength bar
    strengthBar.style.width = score + '%';
    
    if (score === 0) {
        strengthBar.className = 'h-2 rounded-full transition-all duration-300';
        strengthText.textContent = '';
    } else if (score < 50) {
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

function updateRequirement(element, met) {
    const icon = element.querySelector('.requirement-icon');
    if (met) {
        icon.textContent = '✓';
        icon.className = 'requirement-icon mr-2 text-green-500';
        element.className = 'flex items-center text-green-600';
    } else {
        icon.textContent = '○';
        icon.className = 'requirement-icon mr-2';
        element.className = 'flex items-center';
    }
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
});
</script>
