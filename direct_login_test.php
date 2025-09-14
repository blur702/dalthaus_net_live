<?php
// Direct login test - bypasses MVC to test if redirect is server-level
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Login Test</title>
</head>
<body>
    <h1>Direct Login Test</h1>
    <p>If you can see this page, the redirect issue is in the MVC routing, not server config.</p>
    
    <form method="post" action="/admin/login">
        <input type="text" name="username" placeholder="Username" value="kevin"><br>
        <input type="password" name="password" placeholder="Password" value="(130Bpm)"><br>
        <input type="hidden" name="_token" value="<?php echo $_SESSION['_token'] ?? 'test'; ?>">
        <button type="submit">Login</button>
    </form>
    
    <p>Session info:</p>
    <pre><?php print_r($_SESSION); ?></pre>
</body>
</html>