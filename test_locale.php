<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

echo "Current LOCALE from .env: " . ($_ENV['LOCALE'] ?? 'not set') . "\n";

// Test HTTP request to see locale switching
$urls = [
    'https://127.0.0.1:8000/admin/userplus?_locale=en',
    'https://127.0.0.1:8000/admin/userplus?_locale=fr'
];

echo "\nTesting locale switching:\n";
echo "========================\n";

foreach ($urls as $url) {
    echo "\nURL: $url\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 10
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        echo "Error: Could not fetch URL\n";
        continue;
    }
    
    // Check for French vs English text
    $hasFrench = strpos($html, 'Gestion des Utilisateurs') !== false;
    $hasEnglish = strpos($html, 'User Management') !== false;
    
    echo "Contains 'Gestion des Utilisateurs' (French): " . ($hasFrench ? 'YES' : 'NO') . "\n";
    echo "Contains 'User Management' (English): " . ($hasEnglish ? 'YES' : 'NO') . "\n";
    
    // Extract title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
        echo "Page title: " . trim($matches[1]) . "\n";
    }
}

echo "\nDone.\n";