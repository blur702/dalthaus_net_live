<?php
/**
 * Test admin pages for custom element protection
 */

echo "Testing admin layout for custom element protection...\n\n";

// Load the admin layout directly
$layoutPath = __DIR__ . '/src/Views/Layouts/admin.php';

if (!file_exists($layoutPath)) {
    echo "✗ Admin layout not found at: $layoutPath\n";
    exit(1);
}

echo "Checking admin layout file...\n";
$layoutContent = file_get_contents($layoutPath);

$testsPass = true;

// Check for custom element protection
if (strpos($layoutContent, 'window.customElements.define = function') !== false) {
    echo "✓ Custom element override found\n";
} else {
    echo "✗ Custom element override NOT found\n";
    $testsPass = false;
}

// Check for original method storage
if (strpos($layoutContent, 'const originalDefine = window.customElements?.define') !== false) {
    echo "✓ Original method storage found\n";
} else {
    echo "✗ Original method storage NOT found\n";
    $testsPass = false;
}

// Check for element tracking
if (strpos($layoutContent, 'const definedElements = new Set()') !== false) {
    echo "✓ Element tracking mechanism found\n";
} else {
    echo "✗ Element tracking mechanism NOT found\n";
    $testsPass = false;
}

// Check for duplicate prevention logic
if (strpos($layoutContent, 'if (window.customElements.get(name) || definedElements.has(name))') !== false) {
    echo "✓ Duplicate prevention logic found\n";
} else {
    echo "✗ Duplicate prevention logic NOT found\n";
    $testsPass = false;
}

// Check for TinyMCE initialization guard
if (strpos($layoutContent, 'let editorInitialized = false') !== false) {
    echo "✓ TinyMCE initialization guard found\n";
} else {
    echo "✗ TinyMCE initialization guard NOT found\n";
    $testsPass = false;
}

// Check for TinyMCE duplicate removal
if (strpos($layoutContent, "tinymce.get('body').remove()") !== false) {
    echo "✓ TinyMCE duplicate removal found\n";
} else {
    echo "✗ TinyMCE duplicate removal NOT found\n";
    $testsPass = false;
}

// Check that script is placed before TinyMCE load
$protectionPos = strpos($layoutContent, 'window.customElements.define = function');
$tinymcePos = strpos($layoutContent, 'tinymce.min.js');

if ($protectionPos !== false && $tinymcePos !== false && $protectionPos < $tinymcePos) {
    echo "✓ Protection script is placed BEFORE TinyMCE load\n";
} else {
    echo "✗ Protection script placement issue\n";
    $testsPass = false;
}

echo "\n" . str_repeat('=', 50) . "\n";

if ($testsPass) {
    echo "✓ ALL TESTS PASSED\n";
    echo "The custom element protection is properly implemented.\n";
    echo "This should prevent the 'mce-autosize-textarea already defined' error.\n";
} else {
    echo "✗ SOME TESTS FAILED\n";
    echo "The custom element protection may not be fully implemented.\n";
}

echo "\nProtection features:\n";
echo "- Overrides customElements.define to prevent redefinition\n";
echo "- Tracks already defined elements\n";
echo "- Safely handles TinyMCE initialization\n";
echo "- Prevents duplicate editor instances\n";