<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-vapid-keys',
    description: 'Génère les clés VAPID pour les notifications push',
)]
class GenerateVapidKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération des clés VAPID');

        try {
            // Générer une paire de clés
            $keys = $this->generateVapidKeys();

            $io->section('Clés générées avec succès !');
            $io->newLine();

            $io->writeln('Ajoutez ces lignes à votre fichier .env.local :');
            $io->newLine();

            $io->writeln('<info>###> Web Push Notifications ###</info>');
            $io->writeln(sprintf('<comment>VAPID_PUBLIC_KEY</comment>=%s', $keys['publicKey']));
            $io->writeln(sprintf('<comment>VAPID_PRIVATE_KEY</comment>=%s', $keys['privateKey']));
            $io->writeln(sprintf('<comment>VAPID_SUBJECT</comment>=mailto:contact@plongee-venetes.fr'));
            $io->writeln('<info>###< Web Push Notifications ###</info>');

            $io->newLine();
            $io->success('Clés VAPID générées ! Copiez-les dans votre .env.local');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to generate VAPID keys: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function generateVapidKeys(): array
    {
        // Vérifier si OpenSSL est disponible
        if (!function_exists('openssl_pkey_new')) {
            throw new \RuntimeException('OpenSSL extension is required');
        }

        // Générer une paire de clés ECDH sur la courbe P-256 (prime256v1)
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1', // P-256
        ];

        $res = openssl_pkey_new($config);
        if (!$res) {
            throw new \RuntimeException('Failed to generate EC key: ' . openssl_error_string());
        }

        // Extraire la clé privée
        openssl_pkey_export($res, $privateKeyPem);
        $privateKeyDetails = openssl_pkey_get_details($res);

        if (!isset($privateKeyDetails['ec'])) {
            throw new \RuntimeException('Failed to get EC key details');
        }

        // La clé privée au format brut (32 bytes pour P-256)
        $privateKeyRaw = $privateKeyDetails['ec']['d'];
        $privateKey = $this->base64UrlEncode($privateKeyRaw);

        // La clé publique au format non compressé (65 bytes: 0x04 + x + y)
        $publicKeyRaw = $privateKeyDetails['key'];

        // Extraire uniquement les 65 bytes de la clé publique (point non compressé)
        // Format DER a des headers, on prend les derniers 65 bytes
        $publicKeyUncompressed = substr($publicKeyRaw, -65);
        $publicKey = $this->base64UrlEncode($publicKeyUncompressed);

        return [
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
