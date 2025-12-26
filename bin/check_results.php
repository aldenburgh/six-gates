<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = require __DIR__ . '/../config/database.php';
$conn = DatabaseFactory::create($config['default']);

echo "Checking Analysis Results for GOOG...\n";
$sql = "SELECT * FROM stock_analyses WHERE ticker = 'GOOG' ORDER BY id DESC LIMIT 1";
$row = $conn->executeQuery($sql)->fetchAssociative();

if ($row) {
    echo "Found Record ID: " . $row['id'] . "\n";
    echo "Tier: " . $row['quality_tier'] . "\n";
    echo "Gate 1.5 Data: " . substr($row['gate_1_5_data'] ?? '', 0, 50) . "...\n";
    echo "Success!\n";
} else {
    echo "No record found.\n";
}
