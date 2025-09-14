<?php
// test-basic.php - Test ultra basique
echo "<h1>Test basique serveur</h1>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Date/Heure: " . date('Y-m-d H:i:s') . "</p>";

// Test fichiers
$files_to_check = [
    '.env.prod.local',
    'vendor/autoload.php', 
    'public/index.php',
    'src/Kernel.php'
];

echo "<h2>Fichiers:</h2>";
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✅ $file</p>";
    } else {
        echo "<p style='color:red'>❌ $file MANQUANT</p>";
    }
}

// Test dossiers
$dirs_to_check = ['var', 'var/cache', 'var/log', 'public/uploads'];
echo "<h2>Dossiers:</h2>";
foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? "✅ writable" : "❌ pas writable";
        echo "<p>📁 $dir - $writable</p>";
    } else {
        echo "<p style='color:red'>❌ $dir manquant</p>";
    }
}

// Test .env.prod.local
if (file_exists('.env.prod.local')) {
    echo "<h2>Contenu .env.prod.local:</h2>";
    $content = file_get_contents('.env.prod.local');
    // Masquer le mot de passe
    $content = preg_replace('/(:)([^@]+)(@)/', '$1***MASKED***$3', $content);
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
}

echo "<p><strong>Si ce fichier s'affiche, PHP fonctionne. L'erreur 500 vient d'autre part.</strong></p>";
?>