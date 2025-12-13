<?php

// Simple database check
$dbPath = __DIR__ . '/database/database.sqlite';

echo "Database file: $dbPath\n";
echo "File exists: " . (file_exists($dbPath) ? 'Yes' : 'No') . "\n";

if (file_exists($dbPath)) {
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Tables found: " . count($tables) . "\n";
        echo "Tables: " . implode(', ', $tables) . "\n";
        
        // Check for utilities tables specifically
        $utilitiesTables = ['buildings', 'properties', 'tenants', 'meters', 'meter_readings', 'invoices'];
        echo "\nUtilities tables check:\n";
        foreach ($utilitiesTables as $table) {
            $exists = in_array($table, $tables);
            echo "  $table: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
        }
        
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Database file does not exist!\n";
}