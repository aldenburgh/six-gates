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

    $sql = file_get_contents(__DIR__ . '/../database/migrations/001_v6_architecture.sql');

    // Split by ; to run statements (simple migration runner)
    // Actually PDO might allow running the whole blob if configured, but safe to split?
    // The file has complex creates, triggers etc? No triggers seen.
    // Dbal/PDO might support multiple statements if buffer is allowed, but let's try raw exec.

    $conn->executeStatement($sql);
    echo "Migration 001 executed successfully.\n";

} catch (\Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
