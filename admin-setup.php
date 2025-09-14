<?php
// admin-setup.php - Script pour initialiser le site en production
// À exécuter une seule fois via navigateur web puis SUPPRIMER

set_time_limit(300); // 5 minutes max

echo "<h1>🚀 Setup du site Club Subaquatique des Vénètes</h1>";

try {
    require_once 'vendor/autoload.php';
    
    use Symfony\Component\Dotenv\Dotenv;
    
    $dotenv = new Dotenv();
    $dotenv->loadEnv('.env');
    
    echo "<h2>📋 Étape 1: Nettoyage du cache</h2>";
    exec('php bin/console cache:clear --env=prod --no-debug 2>&1', $output, $return);
    if ($return === 0) {
        echo "<p style='color:green'>✅ Cache nettoyé avec succès</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Avertissement cache: " . implode('<br>', $output) . "</p>";
    }
    
    echo "<h2>🗄️ Étape 2: Création des tables</h2>";
    exec('php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1', $output, $return);
    if ($return === 0) {
        echo "<p style='color:green'>✅ Base de données initialisée</p>";
    } else {
        echo "<p style='color:red'>❌ Erreur migrations: " . implode('<br>', $output) . "</p>";
    }
    
    echo "<h2>👤 Étape 3: Création de l'utilisateur admin</h2>";
    exec('php bin/console app:create-admin --env=prod 2>&1', $output, $return);
    if ($return === 0) {
        echo "<p style='color:green'>✅ Utilisateur admin créé</p>";
        echo "<div style='background:#f0f8f0; padding:10px; border:1px solid #4CAF50;'>";
        echo "<strong>📧 Email:</strong> admin@venetes.fr<br>";
        echo "<strong>🔑 Mot de passe:</strong> admin123<br>";
        echo "<em>Changez ce mot de passe dès la première connexion !</em>";
        echo "</div>";
    } else {
        echo "<p style='color:red'>❌ Erreur création admin: " . implode('<br>', $output) . "</p>";
    }
    
    echo "<h2>⚙️ Étape 4: Configuration initiale</h2>";
    exec('php bin/console app:init-site-config --env=prod 2>&1', $output, $return);
    if ($return === 0) {
        echo "<p style='color:green'>✅ Configuration du site initialisée</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Configuration: " . implode('<br>', $output) . "</p>";
    }
    
    echo "<h2>📄 Étape 5: Création des pages par défaut</h2>";
    exec('php bin/console app:create-plongee-pages --env=prod 2>&1', $output, $return);
    if ($return === 0) {
        echo "<p style='color:green'>✅ Pages créées</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Pages: " . implode('<br>', $output) . "</p>";
    }
    
    echo "<h2>🗂️ Étape 6: Création des dossiers</h2>";
    $dirs = ['var/cache', 'var/log', 'public/uploads'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color:green'>✅ Dossier $dir créé</p>";
            } else {
                echo "<p style='color:red'>❌ Impossible de créer $dir</p>";
            }
        } else {
            echo "<p style='color:blue'>ℹ️ Dossier $dir existe déjà</p>";
        }
    }
    
    echo "<h2>🎉 Installation terminée !</h2>";
    echo "<div style='background:#e8f5e8; padding:15px; border:1px solid #4CAF50; margin:20px 0;'>";
    echo "<h3>📝 Prochaines étapes:</h3>";
    echo "<ol>";
    echo "<li><strong>SUPPRIMEZ ce fichier admin-setup.php</strong> pour la sécurité</li>";
    echo "<li>Connectez-vous à <a href='/admin' target='_blank'>/admin</a> avec admin@venetes.fr / admin123</li>";
    echo "<li>Changez le mot de passe admin</li>";
    echo "<li>Configurez les informations du club dans Configuration</li>";
    echo "<li>Créez vos premiers articles et événements</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background:#fff3cd; padding:10px; border:1px solid #ffc107; margin:10px 0;'>";
    echo "<strong>⚠️ IMPORTANT:</strong> Supprimez ce fichier maintenant pour la sécurité !";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Erreur fatale</h2>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez:</p>";
    echo "<ul>";
    echo "<li>Les paramètres de base de données dans .env.prod.local</li>";
    echo "<li>Les permissions des dossiers var/ et public/</li>";
    echo "<li>Que composer install a été exécuté</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
h1 { color: #2c5aa0; }
h2 { color: #fd7e29; border-bottom: 2px solid #fd7e29; padding-bottom: 5px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>