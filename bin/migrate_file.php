<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;

if ($argc < 2) {
    echo "Usage: php bin/migrate_file.php <path_to_sql_file>\n";
    exit(1);
}

$file = $argv[1];
if (!file_exists($file)) {
    // Try relative to project root
    $file = __DIR__ . '/../' . $file;
    if (!file_exists($file)) {
        echo "Error: File not found: $argv[1]\n";
        exit(1);
    }
}

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = require __DIR__ . '/../config/database.php';

try {
    $conn = DatabaseFactory::create($config['default']);
    echo "Connected to Database.\n";

    echo "Running migration: " . basename($file) . "...\n";
    
    $sql = file_get_contents($file);
    
    // Attempt to handle multiple statements if present
    // Simple split by semicolon for basic migrations
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $conn->executeStatement($stmt);
        }
    }

    echo "Migration executed successfully. ✅\n";

} catch (\Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    exit(1);
}
