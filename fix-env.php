<?php
// fix-env.php - Vérifier et corriger .env.prod.local

echo "<h1>🔧 Diagnostic .env.prod.local</h1>";

echo "<h2>📁 Fichiers .env présents:</h2>";
$env_files = ['.env', '.env.prod', '.env.prod.local', '.env.local'];
foreach ($env_files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p style='color:green'>✅ $file ($size bytes)</p>";
        
        // Afficher le contenu (en masquant les mots de passe)
        $content = file_get_contents($file);
        $content_safe = preg_replace('/(:)([^@]+)(@)/', '$1***$3', $content);
        echo "<h3>Contenu $file:</h3>";
        echo "<pre style='background:#f5f5f5; padding:10px;'>" . htmlspecialchars($content_safe) . "</pre>";
        
    } else {
        echo "<p style='color:red'>❌ $file manquant</p>";
    }
}

echo "<h2>✍️ Création du bon .env.prod.local</h2>";

$correct_env = <<<ENV
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://empo8897_venetes_preprod:Vén3t3sPréPr0d@localhost:3306/empo8897_venetes_preprod?serverVersion=8.0&charset=utf8mb4"
###< doctrine/doctrine-bundle ###
ENV;

// Écrire le fichier correct
if (file_put_contents('.env.prod.local', $correct_env)) {
    echo "<p style='color:green'>✅ Fichier .env.prod.local corrigé!</p>";
} else {
    echo "<p style='color:red'>❌ Impossible d'écrire .env.prod.local</p>";
}

echo "<h2>🔌 Test avec les nouveaux paramètres</h2>";

// Test direct avec les bons paramètres
$host = 'localhost';
$database = 'empo8897_venetes_preprod';
$username = 'empo8897_venetes_preprod';
$password = 'Vén3t3sPréPr0d';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p style='color:green'>✅ Connexion serveur MySQL OK</p>";
    
    // Test base spécifique
    $pdo->exec("USE `$database`");
    echo "<p style='color:green'>✅ Accès base '$database' OK</p>";
    
    echo "<p style='color:blue'>🎉 Configuration corrigée! Vous pouvez maintenant relancer deploy.php</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
    
    // Essayer avec 127.0.0.1
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;charset=utf8mb4", $username, $password);
        echo "<p style='color:orange'>⚠️ 127.0.0.1 fonctionne mais pas localhost</p>";
        echo "<p>Utilisez 127.0.0.1 au lieu de localhost dans DATABASE_URL</p>";
    } catch (PDOException $e2) {
        echo "<p style='color:red'>❌ 127.0.0.1 ne fonctionne pas non plus</p>";
        echo "<p>Vérifiez vos paramètres dans cPanel O2switch</p>";
    }
}

echo "<hr>";
echo "<p><strong>📝 Prochaines étapes:</strong></p>";
echo "<ol>";
echo "<li>Supprimez ce fichier fix-env.php</li>";
echo "<li>Relancez: php deploy.php</li>";
echo "<li>Puis allez sur /admin-setup.php</li>";
echo "</ol>";
?>