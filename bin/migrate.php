<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = require __DIR__ . '/../config/database.php';

try {
    $conn = DatabaseFactory::create($config['default']);
    echo "Connected to Database.\n";

    $files = glob(__DIR__ . '/../database/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $filename = basename($file);
        echo "Running migration: $filename...\n";
        $sql = file_get_contents($file);
        $conn->executeStatement($sql);
        echo "  - Completed.\n";
    }
    echo "All migrations executed successfully.\n";

} catch (\Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
