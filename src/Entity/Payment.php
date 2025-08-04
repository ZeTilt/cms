<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\Column(length: 50, unique: true)]
    private string $paymentReference = '';

    #[ORM\Column(length: 30)]
    private string $paymentMethod = ''; // 'card', 'bank_transfer', 'paypal', 'mangopay', 'cash', 'check'

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, processing, completed, failed, cancelled, refunded

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amount = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $providerData = null; // Données du fournisseur de paiement (MangoPay, Stripe, etc.)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $failedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $providerTransactionId = null; // ID de transaction du fournisseur

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->generatePaymentReference();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getPaymentReference(): string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;
        return $this;
    }

    private function generatePaymentReference(): void
    {
        $this->paymentReference = 'PAY-' . date('Y') . '-' . strtoupper(substr(uniqid(), -10));
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        
        // Mettre à jour les timestamps selon le statut
        switch ($status) {
            case 'processing':
                if (!$this->processedAt) {
                    $this->processedAt = new \DateTimeImmutable();
                }
                break;
            case 'completed':
                if (!$this->completedAt) {
                    $this->completedAt = new \DateTimeImmutable();
                }
                break;
            case 'failed':
                if (!$this->failedAt) {
                    $this->failedAt = new \DateTimeImmutable();
                }
                break;
        }
        
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getProviderData(): ?array
    {
        return $this->providerData;
    }

    public function setProviderData(?array $providerData): static
    {
        $this->providerData = $providerData;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getFailedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): static
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getProviderTransactionId(): ?string
    {
        return $this->providerTransactionId;
    }

    public function setProviderTransactionId(?string $providerTransactionId): static
    {
        $this->providerTransactionId = $providerTransactionId;
        return $this;
    }

    /**
     * Marquer le paiement comme réussi
     */
    public function markAsCompleted(string $providerTransactionId = null): static
    {
        $this->setStatus('completed');
        
        if ($providerTransactionId) {
            $this->setProviderTransactionId($providerTransactionId);
        }
        
        return $this;
    }

    /**
     * Marquer le paiement comme échoué
     */
    public function markAsFailed(string $reason = null): static
    {
        $this->setStatus('failed');
        
        if ($reason) {
            $this->setFailureReason($reason);
        }
        
        return $this;
    }

    /**
     * Vérifier si le paiement est terminé (réussi ou échoué)
     */
    public function isFinalized(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled', 'refunded']);
    }

    /**
     * Vérifier si le paiement est réussi
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Obtenir les méthodes de paiement disponibles
     */
    public static function getAvailablePaymentMethods(): array
    {
        return [
            'card' => 'Carte bancaire',
            'bank_transfer' => 'Virement bancaire',
            'paypal' => 'PayPal',
            'mangopay' => 'MangoPay',
            'cash' => 'Espèces',
            'check' => 'Chèque'
        ];
    }

    /**
     * Obtenir les statuts disponibles
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé'
        ];
    }

    /**
     * Obtenir le libellé de la méthode de paiement
     */
    public function getPaymentMethodLabel(): string
    {
        $methods = self::getAvailablePaymentMethods();
        return $methods[$this->paymentMethod] ?? $this->paymentMethod;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabel(): string
    {
        $statuses = self::getAvailableStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Obtenir la couleur CSS du statut
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            'refunded' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * Ajouter des données du fournisseur
     */
    public function addProviderData(string $key, mixed $value): static
    {
        if (!$this->providerData) {
            $this->providerData = [];
        }
        
        $this->providerData[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
        
        return $this;
    }

    /**
     * Obtenir une donnée du fournisseur
     */
    public function getProviderDataValue(string $key): mixed
    {
        return $this->providerData[$key] ?? null;
    }

    /**
     * Méthodes spécifiques MangoPay
     */
    public function getMangoPayPayInId(): ?string
    {
        return $this->getProviderDataValue('mangopay_payin_id');
    }

    public function setMangoPayPayInId(string $payInId): static
    {
        return $this->addProviderData('mangopay_payin_id', $payInId);
    }

    public function getMangoPayWalletId(): ?string
    {
        return $this->getProviderDataValue('mangopay_wallet_id');
    }

    public function setMangoPayWalletId(string $walletId): static
    {
        return $this->addProviderData('mangopay_wallet_id', $walletId);
    }

    public function getMangoPayUserId(): ?string
    {
        return $this->getProviderDataValue('mangopay_user_id');
    }

    public function setMangoPayUserId(string $userId): static
    {
        return $this->addProviderData('mangopay_user_id', $userId);
    }

    public function getPaymentDetails(): ?array
    {
        return $this->getProviderDataValue('payment_details');
    }

    public function setPaymentDetails(array $details): static
    {
        return $this->addProviderData('payment_details', $details);
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        return $this;
    }
}