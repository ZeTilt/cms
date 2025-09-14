<?php
// debug-symfony.php - Debug complet Symfony

echo "DEBUG SYMFONY\n";
echo "=============\n\n";

// 1. Test autoloader et classes de base
echo "1. Test autoloader...\n";
try {
    require_once 'vendor/autoload.php';
    echo "   ✅ Autoloader OK\n";
    
    // Test classes critiques
    $classes = [
        'Symfony\Component\Dotenv\Dotenv',
        'Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait',
        'App\Kernel'
    ];
    
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo "   ✅ $class\n";
        } else {
            echo "   ❌ $class manquante\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erreur autoloader: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test environnement Dotenv
echo "\n2. Test .env...\n";
try {
    $dotenv = new \Symfony\Component\Dotenv\Dotenv();
    $dotenv->loadEnv('.env');
    echo "   ✅ .env chargé\n";
    
    echo "   APP_ENV: " . ($_ENV['APP_ENV'] ?? 'non défini') . "\n";
    echo "   DATABASE_URL: " . (isset($_ENV['DATABASE_URL']) ? 'défini' : 'manquant') . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Erreur .env: " . $e->getMessage() . "\n";
}

// 3. Test Kernel Symfony
echo "\n3. Test Kernel...\n";
try {
    $_ENV['APP_ENV'] = 'prod';
    $_SERVER['APP_ENV'] = 'prod';
    
    if (class_exists('App\Kernel')) {
        $kernel = new \App\Kernel('prod', false);
        echo "   ✅ Kernel créé\n";
        
        $kernel->boot();
        echo "   ✅ Kernel bootés\n";
        
        $container = $kernel->getContainer();
        echo "   ✅ Container disponible\n";
        
        // Test service Doctrine
        if ($container->has('doctrine')) {
            $doctrine = $container->get('doctrine');
            echo "   ✅ Doctrine service OK\n";
            
            try {
                $connection = $doctrine->getConnection();
                $connection->connect();
                echo "   ✅ Connexion Doctrine OK\n";
            } catch (Exception $e) {
                echo "   ❌ Connexion Doctrine: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ❌ Service Doctrine manquant\n";
        }
        
    } else {
        echo "   ❌ Classe App\\Kernel manquante\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur Kernel: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

// 4. Test direct console
echo "\n4. Test console directe...\n";

try {
    // Simuler ce que fait bin/console
    $_SERVER['APP_ENV'] = 'prod';
    putenv('APP_ENV=prod');
    
    $input = new \Symfony\Component\Console\Input\ArrayInput(['command' => 'list']);
    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    
    $kernel = new \App\Kernel('prod', false);
    $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
    $application->setAutoExit(false);
    
    $result = $application->run($input, $output);
    
    if ($result === 0) {
        echo "   ✅ Console fonctionne\n";
        $content = $output->fetch();
        if (strpos($content, 'doctrine:migrations:migrate') !== false) {
            echo "   ✅ Commande migrations disponible\n";
        } else {
            echo "   ❌ Commande migrations manquante\n";
        }
    } else {
        echo "   ❌ Console erreur code: $result\n";
        echo "   Sortie: " . $output->fetch() . "\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Erreur console: " . $e->getMessage() . "\n";
    echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 5. Vérifier les fichiers critiques
echo "\n5. Fichiers critiques...\n";
$files = [
    'src/Kernel.php',
    'config/bundles.php', 
    'config/services.yaml',
    'config/packages/doctrine.yaml',
    'migrations/'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
        if ($file === 'migrations/') {
            $migrations = glob('migrations/*.php');
            echo "       " . count($migrations) . " migrations trouvées\n";
        }
    } else {
        echo "   ❌ $file manquant\n";
    }
}

echo "\nDEBUG TERMINÉ\n";
?>