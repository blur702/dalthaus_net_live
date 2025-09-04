<?php
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current date: " . date('Y-m-d H:i:s') . "<br>";

$test_array = [1, 2, 3];
echo "Array count: " . count($test_array) . "<br>";
echo "Array type: " . gettype($test_array) . "<br>";

if (!empty($test_array)) {
    echo "Array is not empty<br>";
} else {
    echo "Array is empty<br>";
}
?>