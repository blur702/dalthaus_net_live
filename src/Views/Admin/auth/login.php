<div class="text-center mb-8">
    <h2 class="text-3xl font-extrabold text-gray-900">
        Admin Login
    </h2>
    <p class="mt-2 text-sm text-gray-600">
        Sign in to access the admin panel
    </p>
</div>

<!-- Flash Messages -->
<?php if (isset($flash) && !empty($flash)): ?>
    <div class="mb-4 p-4 rounded-md <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-800 border border-red-200' : 'bg-green-50 text-green-800 border border-green-200' ?>">
        <?= $this->escape($flash['message']) ?>
    </div>
<?php endif; ?>

<form class="mt-8 space-y-6" action="/admin/login" method="POST" id="loginForm">
    <input type="hidden" name="_token" value="<?= $this->escape($csrf_token) ?>">
    
    <div class="space-y-4">
        <div>
            <label for="username" class="sr-only">Username</label>
            <input id="username" 
                   name="username" 
                   type="text" 
                   required 
                   class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm bg-white hover:border-gray-400 transition duration-150 ease-in-out" 
                   placeholder="Enter your username"
                   autocomplete="username">
        </div>
        
        <div>
            <label for="password" class="sr-only">Password</label>
            <input id="password" 
                   name="password" 
                   type="password" 
                   required 
                   class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm bg-white hover:border-gray-400 transition duration-150 ease-in-out" 
                   placeholder="Enter your password"
                   autocomplete="current-password">
        </div>
    </div>

    <div>
        <button type="submit" 
                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            Sign in
        </button>
    </div>
</form>

<script>
// Debug form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    console.log('Form submit event triggered');
    console.log('Form action:', this.action);
    console.log('Form method:', this.method);
    
    // Log form data
    const formData = new FormData(this);
    console.log('Form data:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // Don't prevent default - let form submit normally
    console.log('Allowing form to submit...');
});

// Also log if page is reloading
window.addEventListener('beforeunload', function() {
    console.log('Page is about to unload/reload');
});

// Log any redirects
if (window.performance && window.performance.navigation) {
    console.log('Navigation type:', window.performance.navigation.type);
}
</script>

<div class="mt-6 text-center space-y-2">
    <div>
        <a href="/" class="text-sm text-gray-600 hover:text-gray-500">
            &larr; Back to website
        </a>
    </div>
</div>
