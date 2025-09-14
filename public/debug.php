<?php
echo "Environment: " . ($_ENV['APP_ENV'] ?? 'not set') . "\n";
echo "Database URL: " . ($_ENV['DATABASE_URL'] ?? 'not set') . "\n";
echo "Working dir: " . getcwd() . "\n";

// Load environment variables
if (file_exists('../.env.local')) {
    $dotenv = Dotenv\Dotenv::createImmutable('..');
    $dotenv->load();
    echo "After loading .env.local:\n";
    echo "Database URL: " . ($_ENV['DATABASE_URL'] ?? 'not set') . "\n";
}