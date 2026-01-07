<?php
// Simple script to check the last few lines of Laravel log
$logFile = '../storage/logs/laravel.log';

if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20); // Get last 20 lines
    
    echo "<h2>Last 20 lines of Laravel log:</h2>";
    echo "<pre>";
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "Log file not found.";
}
?>