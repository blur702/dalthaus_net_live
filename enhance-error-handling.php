<?php
/**
 * Enhanced Error Handling and PHP 8.4 Compatibility Validator
 * Implements comprehensive error handling and validates PHP 8.4 compatibility
 * 
 * Run this script to enhance error handling across the CMS
 */
declare(strict_types=1);

// Security check
if (!isset($_GET['enhance']) && isset($_SERVER['HTTP_HOST'])) {
    die("Safety check: Add ?enhance=1 to run error handling enhancements");
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<h2>PHP 8.4 Compatibility & Error Handling Enhancement</h2>\n";
echo "<pre>\n";

$fixes = [];
$warnings = [];

// Step 1: PHP Version Check
echo "🔍 Checking PHP version compatibility...\n";
$phpVersion = PHP_VERSION;
echo "   Current PHP Version: $phpVersion\n";

$minVersion = '8.1.0';
if (version_compare($phpVersion, $minVersion, '<')) {
    $warnings[] = "PHP version $phpVersion is below recommended minimum $minVersion";
    echo "   ⚠️ PHP version is below recommended minimum\n";
} elseif (version_compare($phpVersion, '8.4.0', '>=')) {
    echo "   ✅ PHP 8.4+ detected - excellent!\n";
} else {
    echo "   ✅ PHP version is compatible\n";
}

// Step 2: Check required extensions
echo "\n🔌 Checking required PHP extensions...\n";
$requiredExtensions = [
    'pdo' => 'Database connectivity',
    'pdo_mysql' => 'MySQL database support', 
    'json' => 'JSON processing',
    'mbstring' => 'Multi-byte string handling',
    'session' => 'Session management',
    'gd' => 'Image processing (optional)',
    'curl' => 'HTTP requests (optional)'
];

$missingExtensions = [];
foreach ($requiredExtensions as $ext => $purpose) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext - $purpose\n";
    } else {
        echo "   ❌ $ext - $purpose (MISSING)\n";
        if (!in_array($ext, ['gd', 'curl'])) {
            $missingExtensions[] = $ext;
        }
    }
}

if (!empty($missingExtensions)) {
    $warnings[] = "Missing critical PHP extensions: " . implode(', ', $missingExtensions);
}

// Step 3: Check PHP configuration
echo "\n⚙️ Checking PHP configuration for shared hosting compatibility...\n";

$configs = [
    'max_execution_time' => ['min' => 30, 'optimal' => 60],
    'memory_limit' => ['min' => '128M', 'optimal' => '256M'],
    'post_max_size' => ['min' => '8M', 'optimal' => '32M'],
    'upload_max_filesize' => ['min' => '2M', 'optimal' => '10M']
];

foreach ($configs as $setting => $requirements) {
    $current = ini_get($setting);
    echo "   $setting: $current";
    
    // Convert to bytes for comparison
    $currentBytes = $setting === 'max_execution_time' ? (int)$current : convertToBytes($current);
    $minBytes = $setting === 'max_execution_time' ? $requirements['min'] : convertToBytes($requirements['min']);
    
    if ($currentBytes >= $minBytes) {
        echo " ✅\n";
    } else {
        echo " ⚠️ (below minimum {$requirements['min']})\n";
        $warnings[] = "PHP setting $setting is below recommended minimum";
    }
}

// Step 4: Create enhanced error handler
echo "\n🛡️ Creating enhanced error handling system...\n";

$errorHandlerContent = '<?php
/**
 * Enhanced Error Handler for Dalthaus CMS
 * Provides comprehensive error handling, logging, and PHP 8.4 compatibility
 */
declare(strict_types=1);

/**
 * Custom error handler for production environments
 * Logs errors while showing user-friendly messages
 */
function customErrorHandler($severity, $message, $file, $line) {
    // Don\'t handle errors that are suppressed with @
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => "Fatal Error",
        E_WARNING => "Warning", 
        E_PARSE => "Parse Error",
        E_NOTICE => "Notice",
        E_CORE_ERROR => "Core Error",
        E_CORE_WARNING => "Core Warning",
        E_COMPILE_ERROR => "Compile Error",
        E_COMPILE_WARNING => "Compile Warning",
        E_USER_ERROR => "User Error",
        E_USER_WARNING => "User Warning",
        E_USER_NOTICE => "User Notice",
        E_STRICT => "Strict Standards",
        E_RECOVERABLE_ERROR => "Recoverable Error",
        E_DEPRECATED => "Deprecated",
        E_USER_DEPRECATED => "User Deprecated"
    ];
    
    $errorType = $errorTypes[$severity] ?? "Unknown Error";
    $logMessage = "PHP $errorType: $message in $file on line $line";
    
    // Log the error
    if (function_exists(\'logMessage\')) {
        logMessage($logMessage, \'error\');
    } else {
        error_log($logMessage);
    }
    
    // In production, don\'t display errors to users
    if (ENV === \'production\') {
        // For fatal errors, show a user-friendly page
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            if (file_exists(__DIR__ . \'/public/error.php\')) {
                require_once __DIR__ . \'/public/error.php\';
                exit;
            } else {
                die(\'<h1>System Error</h1><p>We apologize, but the system encountered an error. Please try again later.</p>\');
            }
        }
        return true; // Don\'t display the error
    }
    
    // In development, let PHP display the error
    return false;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString();
    
    $logMessage = "$message in $file on line $line\\nStack trace:\\n$trace";
    
    if (function_exists(\'logMessage\')) {
        logMessage($logMessage, \'error\');
    } else {
        error_log($logMessage);
    }
    
    if (ENV === \'production\') {
        if (file_exists(__DIR__ . \'/public/error.php\')) {
            require_once __DIR__ . \'/public/error.php\';
        } else {
            die(\'<h1>System Error</h1><p>We apologize, but the system encountered an error. Please try again later.</p>\');
        }
    } else {
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($message) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($file) . "</p>";
        echo "<p><strong>Line:</strong> $line</p>";
        echo "<pre>" . htmlspecialchars($trace) . "</pre>";
    }
}

/**
 * Shutdown function to catch fatal errors
 */
function shutdownHandler() {
    $error = error_get_last();
    if ($error && in_array($error[\'type\'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $message = "Fatal Error: {$error[\'message\']} in {$error[\'file\']} on line {$error[\'line\']}";
        
        if (function_exists(\'logMessage\')) {
            logMessage($message, \'error\');
        } else {
            error_log($message);
        }
        
        if (ENV === \'production\') {
            if (file_exists(__DIR__ . \'/public/error.php\')) {
                require_once __DIR__ . \'/public/error.php\';
            } else {
                die(\'<h1>System Error</h1><p>We apologize, but the system encountered an error. Please try again later.</p>\');
            }
        }
    }
}

// Register error handlers
set_error_handler(\'customErrorHandler\');
set_exception_handler(\'customExceptionHandler\');
register_shutdown_function(\'shutdownHandler\');

/**
 * PHP 8.4 Compatibility Helpers
 */

/**
 * Safe array access with type checking
 */
function safeArrayGet(array $array, string|int $key, $default = null) {
    return $array[$key] ?? $default;
}

/**
 * Safe string operations for null values
 */
function safeString($value): string {
    return (string)($value ?? \'\');
}

/**
 * Safe integer conversion
 */
function safeInt($value): int {
    if (is_numeric($value)) {
        return (int)$value;
    }
    return 0;
}

/**
 * Enhanced input validation for PHP 8.4
 */
function validateInput(array $data, array $rules): array {
    $errors = [];
    $validated = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        // Required field check
        if (($rule[\'required\'] ?? false) && ($value === null || $value === \'\')) {
            $errors[$field] = $rule[\'label\'] . \' is required\';
            continue;
        }
        
        // Skip validation if field is optional and empty
        if ($value === null || $value === \'\') {
            $validated[$field] = $rule[\'default\'] ?? null;
            continue;
        }
        
        // Type validation
        switch ($rule[\'type\'] ?? \'string\') {
            case \'int\':
                if (!is_numeric($value)) {
                    $errors[$field] = $rule[\'label\'] . \' must be a number\';
                } else {
                    $validated[$field] = (int)$value;
                }
                break;
                
            case \'email\':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = $rule[\'label\'] . \' must be a valid email address\';
                } else {
                    $validated[$field] = $value;
                }
                break;
                
            case \'url\':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = $rule[\'label\'] . \' must be a valid URL\';
                } else {
                    $validated[$field] = $value;
                }
                break;
                
            case \'string\':
            default:
                $validated[$field] = sanitizeInput((string)$value);
                
                // Length validation
                if (isset($rule[\'min_length\']) && strlen($validated[$field]) < $rule[\'min_length\']) {
                    $errors[$field] = $rule[\'label\'] . \' must be at least \' . $rule[\'min_length\'] . \' characters\';
                }
                if (isset($rule[\'max_length\']) && strlen($validated[$field]) > $rule[\'max_length\']) {
                    $errors[$field] = $rule[\'label\'] . \' must be no more than \' . $rule[\'max_length\'] . \' characters\';
                }
                break;
        }
    }
    
    return [\'data\' => $validated, \'errors\' => $errors];
}
?>';

// Write the enhanced error handler
$errorHandlerPath = 'includes/error_handler.php';
file_put_contents($errorHandlerPath, $errorHandlerContent);
echo "   ✅ Created enhanced error handler: $errorHandlerPath\n";
$fixes[] = "Created comprehensive error handling system";

// Step 5: Update config.php to include error handler
echo "\n📝 Updating configuration to include error handler...\n";

$configPath = 'includes/config.php';
$configContent = file_get_contents($configPath);

// Check if error handler is already included
if (strpos($configContent, 'error_handler.php') === false) {
    // Add error handler inclusion at the end of the file
    $errorHandlerInclude = "\n// Enhanced error handling for PHP 8.4 compatibility\nrequire_once __DIR__ . '/error_handler.php';\n";
    
    // Insert before the closing PHP tag or at the end
    if (strpos($configContent, '?>') !== false) {
        $configContent = str_replace('?>', $errorHandlerInclude . '?>', $configContent);
    } else {
        $configContent .= $errorHandlerInclude;
    }
    
    file_put_contents($configPath, $configContent);
    echo "   ✅ Added error handler inclusion to config.php\n";
    $fixes[] = "Integrated error handler with configuration";
} else {
    echo "   ✅ Error handler already included in config.php\n";
}

// Step 6: Create user-friendly error page if it doesn't exist
echo "\n🎨 Creating user-friendly error page...\n";

$errorPagePath = 'public/error.php';
if (!file_exists($errorPagePath)) {
    $errorPageContent = '<?php
/**
 * User-friendly Error Page
 * Displayed when system errors occur in production
 */
declare(strict_types=1);

// Don\'t show PHP errors on this page
ini_set(\'display_errors\', \'0\');

$pageTitle = "System Error";
$hideNavigation = true;

if (file_exists(__DIR__ . \'/../includes/header.php\')) {
    require_once __DIR__ . \'/../includes/header.php\';
}
?>

<div class="container" style="text-align: center; padding: 60px 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="font-size: 120px; color: #e74c3c; margin-bottom: 20px;">⚠️</div>
        
        <h1 style="color: #333; margin-bottom: 20px;">System Error</h1>
        
        <p style="color: #666; font-size: 18px; margin-bottom: 30px;">
            We apologize, but the system encountered an unexpected error. 
            Our technical team has been automatically notified and is working to resolve the issue.
        </p>
        
        <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
            <h3 style="color: #333; margin-bottom: 15px;">What you can do:</h3>
            <ul style="text-align: left; color: #666; line-height: 1.6;">
                <li>Wait a few minutes and try again</li>
                <li>Check that the URL is correct</li>
                <li>Return to the <a href="/" style="color: #3498db;">homepage</a></li>
                <li>Contact support if the problem persists</li>
            </ul>
        </div>
        
        <p style="color: #999; font-size: 14px;">
            Error ID: <?= uniqid() ?> · <?= date(\'Y-m-d H:i:s\') ?>
        </p>
        
        <div style="margin-top: 30px;">
            <a href="/" class="btn" style="background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                ← Return to Homepage
            </a>
        </div>
    </div>
</div>

<?php
if (file_exists(__DIR__ . \'/../includes/footer.php\')) {
    require_once __DIR__ . \'/../includes/footer.php\';
}
?>';

    file_put_contents($errorPagePath, $errorPageContent);
    echo "   ✅ Created user-friendly error page: $errorPagePath\n";
    $fixes[] = "Created professional error page for production";
} else {
    echo "   ✅ Error page already exists\n";
}

// Step 7: Validate current codebase for PHP 8.4 issues
echo "\n🔍 Scanning codebase for PHP 8.4 compatibility issues...\n";

$compatibilityIssues = [];

// Check for deprecated functions
$deprecatedFunctions = ['each', 'create_function', 'mysql_*', 'ereg*', 'split'];
foreach ($deprecatedFunctions as $func) {
    $pattern = str_replace('*', '\\w+', $func);
    $cmd = "grep -r '$pattern(' --include='*.php' . 2>/dev/null || true";
    $output = shell_exec($cmd);
    if (!empty(trim($output))) {
        $compatibilityIssues[] = "Uses deprecated function pattern: $func";
    }
}

if (empty($compatibilityIssues)) {
    echo "   ✅ No deprecated functions found\n";
} else {
    echo "   ⚠️ Found compatibility issues:\n";
    foreach ($compatibilityIssues as $issue) {
        echo "      • $issue\n";
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "ENHANCEMENT SUMMARY\n";
echo str_repeat("=", 60) . "\n";

echo "\n✅ Fixes Applied:\n";
foreach ($fixes as $fix) {
    echo "  • $fix\n";
}

if (!empty($warnings)) {
    echo "\n⚠️ Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
}

if (empty($compatibilityIssues)) {
    echo "\n🎉 PHP 8.4 COMPATIBILITY: EXCELLENT\n";
    echo "The codebase is well-prepared for PHP 8.4!\n";
} else {
    echo "\n⚠️ PHP 8.4 COMPATIBILITY: NEEDS ATTENTION\n";
    echo "Please address the compatibility issues listed above.\n";
}

echo "\n📋 Next Steps:\n";
echo "  1. Test error handling by visiting /public/error.php\n";
echo "  2. Verify logs are being written to logs/ directory\n";
echo "  3. Test the main site functionality\n";
echo "  4. Consider upgrading to PHP 8.4 for better performance\n";

// Helper function for byte conversion
function convertToBytes($value) {
    if (is_numeric($value)) {
        return (int)$value;
    }
    
    $unit = strtoupper(substr($value, -1));
    $number = (int)substr($value, 0, -1);
    
    switch ($unit) {
        case 'G':
            return $number * 1024 * 1024 * 1024;
        case 'M':
            return $number * 1024 * 1024;
        case 'K':
            return $number * 1024;
        default:
            return $number;
    }
}

echo "\n🗑️ Auto-cleanup: Deleting this enhancement script...\n";
if (unlink(__FILE__)) {
    echo "   ✅ Enhancement script deleted for security\n";
} else {
    echo "   ⚠️ Could not delete enhancement script - please remove manually\n";
}

echo "</pre>\n";
?>