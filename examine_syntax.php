<?php
// Examine the corrupted Auth.php file
header("Content-Type: text/plain");

echo "=== EXAMINING AUTH.PHP SYNTAX ERROR ===\n\n";

$authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";

if (file_exists($authFile)) {
    echo "File exists: $authFile\n";
    echo "File size: " . filesize($authFile) . " bytes\n";
    echo "Last modified: " . date("Y-m-d H:i:s", filemtime($authFile)) . "\n\n";
    
    // Read the file content
    $content = file_get_contents($authFile);
    $lines = explode("\n", $content);
    
    echo "Total lines: " . count($lines) . "\n\n";
    
    // Show lines around line 80 (the error line)
    echo "=== CONTENT AROUND LINE 80 (WHERE ERROR OCCURS) ===\n";
    $startLine = max(1, 75);
    $endLine = min(count($lines), 85);
    
    for ($i = $startLine; $i <= $endLine; $i++) {
        $lineContent = isset($lines[$i-1]) ? $lines[$i-1] : "";
        $marker = ($i == 80) ? " <<< ERROR LINE" : "";
        echo sprintf("%3d: %s%s\n", $i, $lineContent, $marker);
    }
    
    echo "\n=== CHECKING FOR COMMON SYNTAX ISSUES ===\n";
    
    // Check for missing closing braces/parentheses
    $openBraces = substr_count($content, "{");
    $closeBraces = substr_count($content, "}");
    $openParens = substr_count($content, "(");
    $closeParens = substr_count($content, ")");
    $openBrackets = substr_count($content, "[");
    $closeBrackets = substr_count($content, "]");
    
    echo "Brace balance: { = $openBraces, } = $closeBraces (diff: " . ($openBraces - $closeBraces) . ")\n";
    echo "Paren balance: ( = $openParens, ) = $closeParens (diff: " . ($openParens - $closeParens) . ")\n";
    echo "Bracket balance: [ = $openBrackets, ] = $closeBrackets (diff: " . ($openBrackets - $closeBrackets) . ")\n";
    
    // Check for specific patterns around line 80
    if (isset($lines[79])) { // Line 80 (0-indexed as 79)
        $line80 = $lines[79];
        echo "\nLine 80 content: \"$line80\"\n";
        echo "Line 80 length: " . strlen($line80) . " characters\n";
        
        // Check for invisible characters
        $visible = preg_replace("/[[:^print:]]/", "?", $line80);
        if ($visible !== $line80) {
            echo "Line 80 with non-printable chars: \"$visible\"\n";
        }
        
        // Check for common issues
        if (strpos($line80, '$flash') !== false) {
            echo "Line contains \$flash variable\n";
        }
        if (strpos($line80, 'function') !== false) {
            echo "Line contains function keyword\n";
        }
    }
    
    // Show the raw hex dump of lines around 80
    echo "\n=== HEX DUMP OF LINES 78-82 ===\n";
    for ($i = 77; $i <= 81; $i++) {
        if (isset($lines[$i-1])) {
            $lineContent = $lines[$i-1];
            $hex = bin2hex($lineContent);
            echo "Line $i: $hex\n";
            echo "      ASCII: " . preg_replace("/[[:^print:]]/", ".", $lineContent) . "\n";
        }
    }
    
} else {
    echo "File does not exist: $authFile\n";
}

echo "\n✅ Examination complete\n";
?>