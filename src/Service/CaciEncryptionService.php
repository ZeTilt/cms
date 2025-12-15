<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CaciEncryptionService
{
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    private Filesystem $filesystem;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/caci_storage')] private string $storageDir,
        #[Autowire('%env(CACI_ENCRYPTION_KEY)%')] private string $encryptionKey
    ) {
        $this->filesystem = new Filesystem();

        // Ensure storage directory exists
        if (!$this->filesystem->exists($this->storageDir)) {
            $this->filesystem->mkdir($this->storageDir, 0700);
        }
    }

    /**
     * Encrypt and store an uploaded file
     *
     * @return string The path to the encrypted file (relative to storage dir)
     */
    public function encryptAndStore(UploadedFile $file, int $userId): string
    {
        // Read file content
        $plaintext = file_get_contents($file->getPathname());

        // Generate random IV
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));

        // Encrypt
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        // Generate unique filename
        $filename = sprintf(
            '%d_%s.enc',
            $userId,
            bin2hex(random_bytes(16))
        );

        $relativePath = date('Y/m') . '/' . $filename;
        $fullPath = $this->storageDir . '/' . $relativePath;

        // Ensure directory exists
        $this->filesystem->mkdir(dirname($fullPath), 0700);

        // Store: IV + tag + ciphertext
        $encryptedData = $iv . $tag . $ciphertext;
        file_put_contents($fullPath, $encryptedData);

        // Secure permissions
        chmod($fullPath, 0600);

        return $relativePath;
    }

    /**
     * Decrypt and return file content
     *
     * @return string The decrypted file content
     */
    public function decrypt(string $relativePath): string
    {
        $fullPath = $this->storageDir . '/' . $relativePath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException('File not found');
        }

        $encryptedData = file_get_contents($fullPath);

        // Extract IV, tag and ciphertext
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($encryptedData, 0, $ivLength);
        $tag = substr($encryptedData, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($encryptedData, $ivLength + self::TAG_LENGTH);

        // Decrypt
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->getKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed - file may be corrupted');
        }

        return $plaintext;
    }

    /**
     * Delete an encrypted file
     */
    public function delete(string $relativePath): void
    {
        $fullPath = $this->storageDir . '/' . $relativePath;

        if ($this->filesystem->exists($fullPath)) {
            // Securely overwrite before deletion
            $fileSize = filesize($fullPath);
            $handle = fopen($fullPath, 'r+');
            if ($handle) {
                fwrite($handle, random_bytes($fileSize));
                fclose($handle);
            }

            $this->filesystem->remove($fullPath);
        }
    }

    /**
     * Get the encryption key (derived from env variable)
     */
    private function getKey(): string
    {
        // Derive a proper 256-bit key from the configured key
        return hash('sha256', $this->encryptionKey, true);
    }

    /**
     * Get the MIME type of a decrypted file
     */
    public function getMimeType(string $relativePath): string
    {
        $content = $this->decrypt($relativePath);

        // Use finfo to detect MIME type from content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($content);
    }

    /**
     * Generate a new encryption key (for setup)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
