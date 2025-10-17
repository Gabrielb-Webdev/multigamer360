<?php
/**
 * Version Check File - MultiGamer360
 * Use this to verify which version is running on Hostinger
 */

header('Content-Type: application/json');

$version_info = [
    'file' => 'product-details.php + header.php',
    'current_version' => '2.3',
    'last_update' => '2025-10-17 21:30',
    'commit' => 'PENDING',
    'status' => 'BOOTSTRAP_FIX_COMPLETE_2025_10_17_21_30',
    'bootstrap_fix' => 'FIXED - Mobile menu script moved to separate file loaded AFTER Bootstrap',
    'changes' => 'Removed inline script from header.php, created mobile-menu.js, loads after Bootstrap',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
];

// Check if product-details.php exists and get its modification time
if (file_exists('product-details.php')) {
    $version_info['file_exists'] = true;
    $version_info['file_modified'] = date('Y-m-d H:i:s', filemtime('product-details.php'));
    
    // Read first 20 lines to check version
    $file_content = file('product-details.php');
    $version_info['file_header'] = implode('', array_slice($file_content, 0, 10));
} else {
    $version_info['file_exists'] = false;
}

echo json_encode($version_info, JSON_PRETTY_PRINT);
