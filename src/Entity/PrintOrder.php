<?php

namespace App\Entity;

use App\Repository\PrintOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrintOrderRepository::class)]
class PrintOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $orderNumber;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $customer;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, sent_to_cewe, confirmed, processing, shipped, delivered, cancelled

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $shippingAddress = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ceweOrderData = null; // Données retournées par l'API CEWE

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ceweOrderId = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\OneToMany(mappedBy: 'printOrder', targetEntity: PrintOrderItem::class, cascade: ['persist', 'remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->orderNumber = $this->generateOrderNumber();
    }

    private function generateOrderNumber(): string
    {
        return 'PO' . date('Ymd') . '_' . strtoupper(substr(uniqid(), -6));
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

    public function getShippingAddress(): ?array
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getCeweOrderData(): ?array
    {
        return $this->ceweOrderData;
    }

    public function setCeweOrderData(?array $ceweOrderData): static
    {
        $this->ceweOrderData = $ceweOrderData;
        return $this;
    }

    public function getCeweOrderId(): ?string
    {
        return $this->ceweOrderId;
    }

    public function setCeweOrderId(?string $ceweOrderId): static
    {
        $this->ceweOrderId = $ceweOrderId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    /**
     * @return Collection<int, PrintOrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PrintOrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setPrintOrder($this);
        }

        return $this;
    }

    public function removeItem(PrintOrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getPrintOrder() === $this) {
                $item->setPrintOrder(null);
            }
        }

        return $this;
    }

    public function getFormattedTotalAmount(): string
    {
        return number_format(floatval($this->totalAmount), 2) . ' €';
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent_to_cewe' => 'bg-blue-100 text-blue-800',
            'confirmed' => 'bg-green-100 text-green-800',
            'processing' => 'bg-indigo-100 text-indigo-800',
            'shipped' => 'bg-purple-100 text-purple-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'sent_to_cewe' => 'Envoyé à CEWE',
            'confirmed' => 'Confirmé',
            'processing' => 'En production',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            default => 'Inconnu'
        };
    }
}