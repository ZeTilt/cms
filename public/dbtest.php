<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables manually
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "DATABASE_URL from dotenv: " . $_ENV['DATABASE_URL'] . "\n";

// Test if we can connect to SQLite directly
try {
    $dbPath = __DIR__ . '/../var/demo.db';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pages");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Direct SQLite connection successful!\n";
    echo "Pages count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Direct SQLite connection failed: " . $e->getMessage() . "\n";
}