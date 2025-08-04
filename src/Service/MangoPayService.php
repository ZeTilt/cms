<?php

namespace App\Service;

use MangoPay\MangoPayApi;
use MangoPay\Card;
use MangoPay\CardRegistration;
use MangoPay\Libraries\Configuration;
use MangoPay\Money;
use MangoPay\PayIn;
use MangoPay\PayInExecutionDetailsDirect;
use MangoPay\PayInPaymentDetailsCard;
use MangoPay\UserNatural;
use MangoPay\Wallet;
use Psr\Log\LoggerInterface;

class MangoPayService
{
    private MangoPayApi $api;
    private LoggerInterface $logger;
    private string $environment;
    private string $clientId;
    private string $clientPassword;

    public function __construct(
        LoggerInterface $logger,
        string $mangoPayClientId,
        string $mangoPayClientPassword,
        string $mangoPayEnvironment = 'sandbox'
    ) {
        $this->logger = $logger;
        $this->clientId = $mangoPayClientId;
        $this->clientPassword = $mangoPayClientPassword;
        $this->environment = $mangoPayEnvironment;
        
        $this->initializeApi();
    }

    private function initializeApi(): void
    {
        $this->api = new MangoPayApi();
        $this->api->Config->ClientId = $this->clientId;
        $this->api->Config->ClientPassword = $this->clientPassword;
        $this->api->Config->TemporaryFolder = sys_get_temp_dir() . '/mangopay/';
        
        // Set environment (sandbox or production)
        if ($this->environment === 'production') {
            $this->api->Config->BaseUrl = 'https://api.mangopay.com';
        } else {
            $this->api->Config->BaseUrl = 'https://api.sandbox.mangopay.com';
        }
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->api->Config->TemporaryFolder)) {
            mkdir($this->api->Config->TemporaryFolder, 0755, true);
        }
    }

    /**
     * Create a natural user (individual)
     */
    public function createNaturalUser(array $userData): ?UserNatural
    {
        try {
            $user = new UserNatural();
            $user->FirstName = $userData['firstName'] ?? '';
            $user->LastName = $userData['lastName'] ?? '';
            $user->Birthday = isset($userData['birthday']) ? (int)$userData['birthday'] : null;
            $user->Nationality = $userData['nationality'] ?? 'FR';
            $user->CountryOfResidence = $userData['countryOfResidence'] ?? 'FR';
            $user->Email = $userData['email'] ?? '';
            
            if (isset($userData['address'])) {
                $user->Address = $userData['address'];
            }

            $createdUser = $this->api->Users->Create($user);
            
            $this->logger->info('MangoPay natural user created', [
                'mangopay_user_id' => $createdUser->Id,
                'email' => $createdUser->Email
            ]);
            
            return $createdUser;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MangoPay natural user', [
                'error' => $e->getMessage(),
                'userData' => $userData
            ]);
            return null;
        }
    }

    /**
     * Create a wallet for a user
     */
    public function createWallet(string $mangoPayUserId, string $description = 'Main wallet', string $currency = 'EUR'): ?Wallet
    {
        try {
            $wallet = new Wallet();
            $wallet->Owners = [$mangoPayUserId];
            $wallet->Description = $description;
            $wallet->Currency = $currency;

            $createdWallet = $this->api->Wallets->Create($wallet);
            
            $this->logger->info('MangoPay wallet created', [
                'wallet_id' => $createdWallet->Id,
                'user_id' => $mangoPayUserId
            ]);
            
            return $createdWallet;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MangoPay wallet', [
                'error' => $e->getMessage(),
                'user_id' => $mangoPayUserId
            ]);
            return null;
        }
    }

    /**
     * Create a card registration for tokenizing card data
     */
    public function createCardRegistration(string $mangoPayUserId, string $currency = 'EUR'): ?CardRegistration
    {
        try {
            $cardRegistration = new CardRegistration();
            $cardRegistration->UserId = $mangoPayUserId;
            $cardRegistration->Currency = $currency;

            $createdCardRegistration = $this->api->CardRegistrations->Create($cardRegistration);
            
            $this->logger->info('MangoPay card registration created', [
                'card_registration_id' => $createdCardRegistration->Id,
                'user_id' => $mangoPayUserId
            ]);
            
            return $createdCardRegistration;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MangoPay card registration', [
                'error' => $e->getMessage(),
                'user_id' => $mangoPayUserId
            ]);
            return null;
        }
    }

    /**
     * Process a direct card payment
     */
    public function processDirectCardPayment(
        string $authorId,
        string $walletId,
        string $cardId,
        int $amountCents,
        string $currency = 'EUR',
        string $secureModeReturnUrl = null
    ): ?PayIn {
        try {
            $payIn = new PayIn();
            $payIn->CreditedWalletId = $walletId;
            $payIn->AuthorId = $authorId;
            
            // Payment details
            $payIn->PaymentDetails = new PayInPaymentDetailsCard();
            $payIn->PaymentDetails->CardId = $cardId;
            
            // Execution details
            $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
            $payIn->ExecutionDetails->SecureModeReturnURL = $secureModeReturnUrl;
            
            // Amount
            $payIn->DebitedFunds = new Money();
            $payIn->DebitedFunds->Amount = $amountCents;
            $payIn->DebitedFunds->Currency = $currency;
            
            $payIn->Fees = new Money();
            $payIn->Fees->Amount = 0; // No fees for now
            $payIn->Fees->Currency = $currency;

            $createdPayIn = $this->api->PayIns->Create($payIn);
            
            $this->logger->info('MangoPay direct card payment processed', [
                'payin_id' => $createdPayIn->Id,
                'amount' => $amountCents,
                'status' => $createdPayIn->Status
            ]);
            
            return $createdPayIn;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process MangoPay direct card payment', [
                'error' => $e->getMessage(),
                'author_id' => $authorId,
                'amount' => $amountCents
            ]);
            return null;
        }
    }

    /**
     * Get user by MangoPay ID
     */
    public function getUser(string $mangoPayUserId): ?UserNatural
    {
        try {
            return $this->api->Users->Get($mangoPayUserId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MangoPay user', [
                'error' => $e->getMessage(),
                'user_id' => $mangoPayUserId
            ]);
            return null;
        }
    }

    /**
     * Get wallet by ID
     */
    public function getWallet(string $walletId): ?Wallet
    {
        try {
            return $this->api->Wallets->Get($walletId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MangoPay wallet', [
                'error' => $e->getMessage(),
                'wallet_id' => $walletId
            ]);
            return null;
        }
    }

    /**
     * Get payment by ID
     */
    public function getPayIn(string $payInId): ?PayIn
    {
        try {
            return $this->api->PayIns->Get($payInId);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MangoPay payment', [
                'error' => $e->getMessage(),
                'payin_id' => $payInId
            ]);
            return null;
        }
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientPassword);
    }

    /**
     * Get the current environment
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Create a webhook
     */
    public function createWebhook(array $webhookData): mixed
    {
        try {
            $hook = new \MangoPay\Hook();
            $hook->EventType = $webhookData['EventType'];
            $hook->Url = $webhookData['Url'];
            
            $createdHook = $this->api->Hooks->Create($hook);
            
            $this->logger->info('MangoPay webhook created', [
                'hook_id' => $createdHook->Id,
                'event_type' => $webhookData['EventType']
            ]);
            
            return $createdHook;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create MangoPay webhook', [
                'error' => $e->getMessage(),
                'event_type' => $webhookData['EventType'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Update a webhook
     */
    public function updateWebhook(string $hookId, array $updateData): mixed
    {
        try {
            $hook = $this->api->Hooks->Get($hookId);
            
            if (isset($updateData['Url'])) {
                $hook->Url = $updateData['Url'];
            }
            if (isset($updateData['Status'])) {
                $hook->Status = $updateData['Status'];
            }
            
            $updatedHook = $this->api->Hooks->Update($hook);
            
            $this->logger->info('MangoPay webhook updated', [
                'hook_id' => $hookId,
                'status' => $updateData['Status'] ?? 'unchanged'
            ]);
            
            return $updatedHook;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update MangoPay webhook', [
                'error' => $e->getMessage(),
                'hook_id' => $hookId
            ]);
            throw $e;
        }
    }

    /**
     * List all webhooks
     */
    public function listWebhooks(): array
    {
        try {
            $hooks = $this->api->Hooks->GetAll();
            
            $this->logger->info('Listed MangoPay webhooks', [
                'count' => count($hooks)
            ]);
            
            return $hooks;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to list MangoPay webhooks', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get a specific webhook
     */
    public function getWebhook(string $hookId): mixed
    {
        try {
            $hook = $this->api->Hooks->Get($hookId);
            
            $this->logger->info('Retrieved MangoPay webhook', [
                'hook_id' => $hookId
            ]);
            
            return $hook;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MangoPay webhook', [
                'error' => $e->getMessage(),
                'hook_id' => $hookId
            ]);
            throw $e;
        }
    }
}