<?php
// Script to get the actual error from Laravel log
$logFile = '../storage/logs/laravel.log';

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    
    // Find the last error entry
    $lines = explode("\n", $content);
    $errorLines = [];
    $inError = false;
    
    // Look for recent errors (last 100 lines)
    $recentLines = array_slice($lines, -200);
    
    foreach ($recentLines as $line) {
        if (strpos($line, '[') === 0 && (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false)) {
            $inError = true;
            $errorLines = [$line];
        } elseif ($inError && (strpos($line, '[') === 0)) {
            // New log entry, stop collecting
            break;
        } elseif ($inError) {
            $errorLines[] = $line;
        }
    }
    
    if (!empty($errorLines)) {
        echo "<h2>Latest Error:</h2>";
        echo "<pre>";
        foreach ($errorLines as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<h2>No recent errors found. Last 10 lines:</h2>";
        echo "<pre>";
        $lastLines = array_slice($lines, -10);
        foreach ($lastLines as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    }
} else {
    echo "Log file not found.";
}
?>