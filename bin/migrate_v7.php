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

    echo "Running Migration 003 (Slack Integration)...\n";
    $sql = file_get_contents(__DIR__ . '/../database/migrations/003_v7_slack_integration.sql');

    // Split by ; is risky for JSON usage, but let's try executeStatement which might handle multiple stats or fail
    // Doctrine DBAL executeStatement usually runs one statement. 
    // We should split manually if DBAL doesn't support multi-query.
    // However, simplest way for this specific file (which has no weird triggers) is strict split or just relying on PDO emulation.

    // Better strategy: Split by "-- ... \n" or just raw string if driver allows.
    // Let's try raw first.

    $conn->executeStatement($sql); // Might fail if multi-query not enabled.

    echo "Migration 003 executed successfully.\n";

} catch (\Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
    // If it failed due to syntax, we might need to split.
    // Attempting rudimentary split:
    if (str_contains($e->getMessage(), 'syntax') || str_contains($e->getMessage(), 'You have an error in your SQL syntax')) {
        echo "Retrying with statement splitting...\n";
        $statements = explode(';', $sql);
        foreach ($statements as $stmt) {
            if (trim($stmt)) {
                $conn->executeStatement($stmt);
            }
        }
        echo "Migration 003 executed successfully (split).\n";
    }
}
