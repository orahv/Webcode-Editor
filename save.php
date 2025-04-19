<?php
session_start(); 
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'var') {
    http_response_code(403);
    exit("Access denied.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filepath = $_POST['filepath'] ?? '';
    $code = $_POST['code'] ?? ''; 
    $baseDir = __DIR__ . '/';  
    $fullPath = realpath($baseDir . '/' . $filepath); 
  
    if (strpos($fullPath, realpath($baseDir)) === 0) {
        file_put_contents($fullPath, $code);
        echo 'Saved!';
    } else {
        http_response_code(403);
        echo 'Invalid path.';
    }
}
