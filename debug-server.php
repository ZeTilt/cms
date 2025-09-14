<?php
// debug-server.php - Diagnostic basique du serveur
// Uploadez ce fichier et allez sur https://venetes.dhuicque.fr/debug-server.php

echo "<h1>🔧 Diagnostic serveur O2switch</h1>";

// 1. Version PHP
echo "<h2>📋 Informations PHP</h2>";
echo "<p><strong>Version PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Symfony minimum:</strong> 8.2+ (requis)</p>";

if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "<p style='color:red'>❌ Version PHP insuffisante! Symfony 7.3 nécessite PHP 8.2+</p>";
} else {
    echo "<p style='color:green'>✅ Version PHP compatible</p>";
}

// 2. Extensions PHP requises
echo "<h2>🧩 Extensions PHP</h2>";
$required_extensions = ['ctype', 'iconv', 'mysqli', 'pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green'>✅ $ext</p>";
    } else {
        echo "<p style='color:red'>❌ $ext (manquant)</p>";
    }
}

// 3. Fichiers critiques
echo "<h2>📁 Fichiers critiques</h2>";
$files = [
    'vendor/autoload.php' => 'Autoloader Composer',
    '.env' => 'Configuration base',
    '.env.prod.local' => 'Configuration production',
    'public/index.php' => 'Point d\'entrée Symfony'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "<p style='color:green'>✅ $file ($desc)</p>";
    } else {
        echo "<p style='color:red'>❌ $file ($desc) - MANQUANT</p>";
    }
}

// 4. Permissions dossiers
echo "<h2>🔐 Permissions</h2>";
$dirs = ['var', 'var/cache', 'var/log', 'public/uploads'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        if (is_writable($dir)) {
            echo "<p style='color:green'>✅ $dir (permissions: $perms - OK)</p>";
        } else {
            echo "<p style='color:orange'>⚠️ $dir (permissions: $perms - pas writable)</p>";
        }
    } else {
        echo "<p style='color:red'>❌ $dir - dossier manquant</p>";
    }
}

// 5. Test connexion base de données
echo "<h2>🗄️ Base de données</h2>";
if (file_exists('.env.prod.local')) {
    $env_content = file_get_contents('.env.prod.local');
    if (preg_match('/DATABASE_URL="mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/([^?"]+)/', $env_content, $matches)) {
        $host = $matches[3];
        $port = $matches[4];
        $database = $matches[5];
        $username = $matches[1];
        $password = $matches[2];
        
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
            echo "<p style='color:green'>✅ Connexion base de données OK</p>";
            echo "<p>Base: $database sur $host:$port</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Erreur connexion base: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Format DATABASE_URL incorrect dans .env.prod.local</p>";
    }
} else {
    echo "<p style='color:red'>❌ Fichier .env.prod.local manquant</p>";
}

// 6. Logs d'erreur PHP
echo "<h2>🐛 Erreurs récentes</h2>";
$error_log = ini_get('error_log');
echo "<p>Log d'erreur configuré: $error_log</p>";

if (file_exists('var/log/prod.log')) {
    $log_content = file_get_contents('var/log/prod.log');
    $recent_logs = array_slice(explode("\n", $log_content), -10);
    echo "<h3>Dernières erreurs Symfony:</h3>";
    echo "<pre style='background:#f5f5f5; padding:10px; font-size:12px;'>";
    echo htmlspecialchars(implode("\n", $recent_logs));
    echo "</pre>";
}

// 7. Test simple Symfony
echo "<h2>🎯 Test Symfony</h2>";
if (file_exists('vendor/autoload.php')) {
    try {
        require_once 'vendor/autoload.php';
        echo "<p style='color:green'>✅ Autoloader Composer chargé</p>";
        
        if (class_exists('Symfony\Component\Dotenv\Dotenv')) {
            echo "<p style='color:green'>✅ Classes Symfony disponibles</p>";
        } else {
            echo "<p style='color:red'>❌ Classes Symfony non trouvées</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Erreur autoloader: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ vendor/autoload.php manquant - exécutez composer install</p>";
}

echo "<hr>";
echo "<p><strong>📝 Pour résoudre l'erreur 500:</strong></p>";
echo "<ol>";
echo "<li>Corrigez les éléments marqués ❌</li>";
echo "<li>Vérifiez les logs d'erreur de votre hébergeur</li>";
echo "<li>Supprimez ce fichier debug-server.php après diagnostic</li>";
echo "</ol>";

phpinfo();
?>