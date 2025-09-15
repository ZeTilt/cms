<?php

// Script de test pour vérifier la configuration email
require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Configuration SMTP
$dsn = 'smtp://no-reply%40venetes.dhuicque.fr:jEhRypjiyC1P@venetes.dhuicque.fr:465?encryption=ssl&auth_mode=login';

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('no-reply@venetes.dhuicque.fr')
        ->to('votre-email@example.com') // Remplacez par votre email de test
        ->subject('Test de configuration SMTP - Club Subaquatique des Vénètes')
        ->html('<h1>Test réussi !</h1><p>La configuration email SMTP fonctionne correctement.</p>')
        ->text('Test réussi ! La configuration email SMTP fonctionne correctement.');

    $mailer->send($email);
    
    echo "✅ Email de test envoyé avec succès !\n";
    echo "Vérifiez votre boîte de réception.\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de l'envoi de l'email :\n";
    echo $e->getMessage() . "\n";
    
    // Essai avec les paramètres non-SSL en fallback
    echo "\n🔄 Tentative avec les paramètres non-SSL...\n";
    
    try {
        $dsnFallback = 'smtp://no-reply%40venetes.dhuicque.fr:jEhRypjiyC1P@mail.venetes.dhuicque.fr:26?auth_mode=login';
        $transportFallback = Transport::fromDsn($dsnFallback);
        $mailerFallback = new Mailer($transportFallback);
        
        $mailerFallback->send($email);
        echo "✅ Email envoyé avec succès avec les paramètres non-SSL !\n";
        echo "Note: Vous devriez utiliser cette configuration :\n";
        echo "MAILER_DSN=smtp://no-reply%40venetes.dhuicque.fr:jEhRypjiyC1P@mail.venetes.dhuicque.fr:26?auth_mode=login\n";
        
    } catch (Exception $e2) {
        echo "❌ Erreur avec les paramètres non-SSL aussi :\n";
        echo $e2->getMessage() . "\n";
    }
}