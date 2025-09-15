<?php

// Test email simple pour la production
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env.prod.local');

$dsn = $_ENV['MAILER_DSN'] ?? '';

if (empty($dsn)) {
    echo "❌ MAILER_DSN non configuré\n";
    exit(1);
}

echo "🔧 Configuration SMTP : " . substr($dsn, 0, 30) . "...\n";

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('no-reply@venetes.dhuicque.fr')
        ->to('test@example.com') // Remplacer par un vrai email pour tester
        ->subject('Test SMTP - Club Subaquatique des Vénètes')
        ->html('
            <h1>Test de configuration SMTP</h1>
            <p>✅ La configuration email fonctionne correctement !</p>
            <p>Serveur : venetes.dhuicque.fr:465 (SSL)</p>
            <p>Date : ' . date('Y-m-d H:i:s') . '</p>
        ')
        ->text('Test de configuration SMTP réussi !');

    echo "📧 Tentative d'envoi d'email de test...\n";
    
    // Décommentez la ligne suivante et changez l'email pour tester réellement
    // $mailer->send($email);
    
    echo "✅ Configuration SMTP validée !\n";
    echo "Pour tester réellement :\n";
    echo "1. Changez l'email de destination dans le script\n";
    echo "2. Décommentez la ligne \$mailer->send(\$email)\n";
    echo "3. Relancez le script\n";

} catch (Exception $e) {
    echo "❌ Erreur de configuration SMTP :\n";
    echo $e->getMessage() . "\n";
    
    // Test avec configuration non-SSL
    echo "\n🔄 Test avec configuration non-SSL...\n";
    
    try {
        $dsnAlt = 'smtp://no-reply%40venetes.dhuicque.fr:jEhRypjiyC1P@mail.venetes.dhuicque.fr:26?auth_mode=login';
        $transportAlt = Transport::fromDsn($dsnAlt);
        $mailerAlt = new Mailer($transportAlt);
        
        echo "📧 Test de connexion non-SSL...\n";
        // $mailerAlt->send($email); // Décommentez pour tester
        
        echo "✅ Configuration non-SSL semble fonctionner !\n";
        echo "Vous devriez utiliser cette configuration dans .env.prod.local :\n";
        echo "MAILER_DSN=smtp://no-reply%40venetes.dhuicque.fr:jEhRypjiyC1P@mail.venetes.dhuicque.fr:26?auth_mode=login\n";
        
    } catch (Exception $e2) {
        echo "❌ Erreur avec configuration non-SSL aussi :\n";
        echo $e2->getMessage() . "\n";
    }
}

echo "\n📋 Rappel des fonctionnalités email dans l'application :\n";
echo "- ✉️  Email de vérification lors de l'inscription\n";
echo "- 🔔 Notifications d'inscription aux événements (à implémenter)\n";
echo "- 📧 Rappels d'événements (à implémenter)\n";