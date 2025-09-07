<div class="text-center mb-8">
    <h2 class="text-3xl font-extrabold text-gray-900">
        Reset Password
    </h2>
    <p class="mt-2 text-sm text-gray-600">
        Enter your new password below
    </p>
</div>

<!-- Flash Messages -->
<?php if (isset($flash) && !empty($flash)): ?>
    <?php foreach ($flash as $type => $message): ?>
    <div class="mb-4 p-4 rounded-md <?= $type === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : ($type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-blue-50 text-blue-800 border border-blue-200') ?>">
        <?= $this->escape($message) ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<form class="mt-8 space-y-6" action="/admin/reset-password" method="POST" id="reset-form">
    <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
    <input type="hidden" name="token" value="<?= $this->escape($token ?? '') ?>">
    
    <div class="space-y-4">
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">
                New Password
            </label>
            <div class="mt-1">
                <input id="password" 
                       name="password" 
                       type="password" 
                       required 
                       minlength="8"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                       placeholder="Enter new password">
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Must be at least 8 characters long
            </p>
        </div>
        
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm New Password
            </label>
            <div class="mt-1">
                <input id="password_confirmation" 
                       name="password_confirmation" 
                       type="password" 
                       required 
                       minlength="8"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                       placeholder="Confirm new password">
            </div>
        </div>
    </div>

    <div id="password-strength" class="hidden">
        <div class="text-sm font-medium text-gray-700 mb-1">Password Strength</div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div id="strength-bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
        <p id="strength-text" class="mt-1 text-xs text-gray-500"></p>
    </div>

    <div>
        <button type="submit" 
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Reset Password
        </button>
    </div>
</form>

<div class="mt-6 text-center">
    <a href="/admin/login" class="text-sm text-blue-600 hover:text-blue-500">
        &larr; Back to login
    </a>
</div>

<script>
// Password strength checker
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthDiv = document.getElementById('password-strength');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (password.length === 0) {
        strengthDiv.classList.add('hidden');
        return;
    }
    
    strengthDiv.classList.remove('hidden');
    
    let strength = 0;
    let strengthLabel = 'Very Weak';
    let strengthColor = 'bg-red-500';
    
    // Check password strength
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    switch(strength) {
        case 0:
        case 1:
            strengthLabel = 'Very Weak';
            strengthColor = 'bg-red-500';
            break;
        case 2:
            strengthLabel = 'Weak';
            strengthColor = 'bg-orange-500';
            break;
        case 3:
            strengthLabel = 'Fair';
            strengthColor = 'bg-yellow-500';
            break;
        case 4:
            strengthLabel = 'Good';
            strengthColor = 'bg-blue-500';
            break;
        case 5:
            strengthLabel = 'Strong';
            strengthColor = 'bg-green-500';
            break;
    }
    
    strengthBar.className = 'h-2 rounded-full transition-all duration-300 ' + strengthColor;
    strengthBar.style.width = (strength * 20) + '%';
    strengthText.textContent = strengthLabel;
});

// Validate password match
document.getElementById('reset-form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    
    if (password !== confirmation) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>
