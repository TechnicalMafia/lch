<?php
// Create this file as /lch/debug_nav.php
// This will help us test if the navigation paths are working

echo "<h1>Navigation Debug Test</h1>";
echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Server Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";

echo "<h2>Testing File Paths:</h2>";

$testPaths = [
    '/lch/modules/reception/add_patient.php',
    '/lch/modules/admin/users.php',
    '/lch/modules/doctor/patient_queue.php',
    '/lch/modules/lab/lab_tokens.php'
];

foreach ($testPaths as $path) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
    $exists = file_exists($fullPath);
    $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
    echo "<p>{$path} - {$status}</p>";
    if (!$exists) {
        echo "<p style='color: red;'>Expected at: {$fullPath}</p>";
    }
}

echo "<h2>Direct Navigation Links:</h2>";
echo "<p><a href='/lch/modules/reception/add_patient.php'>Test Add Patient</a></p>";
echo "<p><a href='/lch/modules/admin/users.php'>Test Manage Users</a></p>";
echo "<p><a href='/lch/index.php'>Back to Dashboard</a></p>";

echo "<h2>Directory Contents:</h2>";
echo "<h3>/lch/modules/:</h3>";
if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/lch/modules/')) {
    $dirs = scandir($_SERVER['DOCUMENT_ROOT'] . '/lch/modules/');
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..') {
            echo "<p>📁 {$dir}</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ /lch/modules/ directory not found!</p>";
}
?>