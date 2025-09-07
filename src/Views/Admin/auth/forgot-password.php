<div class="text-center mb-8">
    <h2 class="text-3xl font-extrabold text-gray-900">
        Password Reset
    </h2>
    <p class="mt-2 text-sm text-gray-600">
        Enter your email address and we'll send you a link to reset your password
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

<form class="mt-8 space-y-6" action="/admin/forgot-password" method="POST">
    <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
    
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
            Email Address
        </label>
        <div class="mt-1">
            <input id="email" 
                   name="email" 
                   type="email" 
                   required 
                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                   placeholder="Enter your email address"
                   autocomplete="email">
        </div>
        <p class="mt-2 text-sm text-gray-500">
            Please enter the email address associated with your account
        </p>
    </div>

    <div>
        <button type="submit" 
                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Get New Password
        </button>
    </div>
</form>

<div class="mt-6 text-center">
    <a href="/admin/login" class="text-sm text-blue-600 hover:text-blue-500">
        &larr; Back to login
    </a>
</div>
