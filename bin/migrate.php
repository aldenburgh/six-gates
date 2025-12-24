<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];

echo "Migrating Database: $db at $host:$port\n";

try {
    // Connect without DB first to create it
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If --fresh is passed, drop the database first
    if (in_array('--fresh', $argv)) {
        echo "Dropping database `$db`...\n";
        $pdo->exec("DROP DATABASE IF EXISTS `$db`");
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");

    // Apply migrations
    $migrations = glob(__DIR__ . '/../database/migrations/*.sql');
    sort($migrations);

    foreach ($migrations as $file) {
        echo "Applying " . basename($file) . "... ";
        $sql = file_get_contents($file);

        // Split by semicolon? Or just execute raw if no delimiters.
        // Simple SQL files often have multiple statements.
        // PDO's exec() runs one statement. We might need to handle multi-queries.
        // But usually naive exec() works if the driver allows emulation.
        // Let's try raw exec first, if fails we split.
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (empty($stmt))
                continue;
            try {
                $pdo->exec($stmt);
                echo "Executed statement.\n";
            } catch (PDOException $e) {
                // Check if "table already exists" (Code 42S01) or "duplicate column" (42S21)
                $msg = $e->getMessage();
                if ($e->getCode() == '42S01' || strpos($msg, 'already exists') !== false || strpos($msg, 'Duplicate column') !== false) {
                    echo "Skipped (Already exists)\n";
                } else {
                    echo "Error executing: " . substr($stmt, 0, 50) . "...\n";
                    throw $e;
                }
            }
        }
        echo "Done file.\n";
    }

    echo "Migration Complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
