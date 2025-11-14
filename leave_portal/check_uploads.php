<?php
$folder = __DIR__ . '/uploads';

if (is_readable($folder)) {
    echo "Uploads folder is readable by PHP/Apache.<br>";
} else {
    echo "Uploads folder is NOT readable by PHP/Apache.<br>";
}

$test_file = $folder . '/test.txt';
if (file_exists($test_file)) {
    echo "File exists: test.txt";
} else {
    echo "No test file found in uploads folder.";
}
?>
