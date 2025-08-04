<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $orderNumber = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, confirmed, paid, processing, shipped, delivered, cancelled, refunded

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $taxAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $shippingAmount = '0.00';

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $billingAddress = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orderItems;

    #[ORM\OneToMany(mappedBy: 'order', targetEntity: Payment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orderItems = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->generateOrderNumber();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    private function generateOrderNumber(): void
    {
        $this->orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -8));
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function setCustomer(User $customer): static
    {
        $this->customer = $customer;
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
            case 'confirmed':
                if (!$this->confirmedAt) {
                    $this->confirmedAt = new \DateTimeImmutable();
                }
                break;
            case 'shipped':
                if (!$this->shippedAt) {
                    $this->shippedAt = new \DateTimeImmutable();
                }
                break;
            case 'delivered':
                if (!$this->deliveredAt) {
                    $this->deliveredAt = new \DateTimeImmutable();
                }
                break;
        }
        
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getShippingAmount(): string
    {
        return $this->shippingAmount;
    }

    public function setShippingAmount(string $shippingAmount): static
    {
        $this->shippingAmount = $shippingAmount;
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

    public function getBillingAddress(): ?array
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?array $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
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

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrder() === $this) {
                $orderItem->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setOrder($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getOrder() === $this) {
                $payment->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * Calculer le montant total basé sur les articles
     */
    public function calculateTotalAmount(): void
    {
        $subtotal = 0;
        
        foreach ($this->orderItems as $item) {
            $subtotal += floatval($item->getTotalPrice());
        }
        
        $this->totalAmount = number_format($subtotal + floatval($this->taxAmount) + floatval($this->shippingAmount), 2, '.', '');
    }

    /**
     * Obtenir le montant sous-total (sans taxes ni frais de port)
     */
    public function getSubtotalAmount(): string
    {
        $subtotal = 0;
        
        foreach ($this->orderItems as $item) {
            $subtotal += floatval($item->getTotalPrice());
        }
        
        return number_format($subtotal, 2, '.', '');
    }

    /**
     * Vérifier si la commande est payée
     */
    public function isPaid(): bool
    {
        $totalPaid = 0;
        
        foreach ($this->payments as $payment) {
            if ($payment->getStatus() === 'completed') {
                $totalPaid += floatval($payment->getAmount());
            }
        }
        
        return $totalPaid >= floatval($this->totalAmount);
    }

    /**
     * Obtenir le montant restant à payer
     */
    public function getRemainingAmount(): string
    {
        $totalPaid = 0;
        
        foreach ($this->payments as $payment) {
            if ($payment->getStatus() === 'completed') {
                $totalPaid += floatval($payment->getAmount());
            }
        }
        
        $remaining = floatval($this->totalAmount) - $totalPaid;
        return number_format(max(0, $remaining), 2, '.', '');
    }

    /**
     * Obtenir les statuts disponibles
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'paid' => 'Payée',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
            'refunded' => 'Remboursée'
        ];
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
            'confirmed' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}