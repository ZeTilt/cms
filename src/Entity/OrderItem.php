<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private Order $order;

    #[ORM\Column(length: 50)]
    private string $itemType = ''; // 'product', 'service', 'event_registration', 'gallery_access'

    #[ORM\Column]
    private int $itemId = 0; // ID de l'entité référencée

    #[ORM\Column(length: 255)]
    private string $itemName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $itemDescription = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $taxRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $taxAmount = '0.00';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $itemOptions = null; // Options spécifiques à l'article (taille, couleur, etc.)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function setItemId(int $itemId): static
    {
        $this->itemId = $itemId;
        return $this;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): static
    {
        $this->itemName = $itemName;
        return $this;
    }

    public function getItemDescription(): ?string
    {
        return $this->itemDescription;
    }

    public function setItemDescription(?string $itemDescription): static
    {
        $this->itemDescription = $itemDescription;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);
        $this->calculateTotalPrice();
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->calculateTotalPrice();
        return $this;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function setTaxRate(?string $taxRate): static
    {
        $this->taxRate = $taxRate;
        $this->calculateTaxAmount();
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

    public function getItemOptions(): ?array
    {
        return $this->itemOptions;
    }

    public function setItemOptions(?array $itemOptions): static
    {
        $this->itemOptions = $itemOptions;
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

    /**
     * Calculer le prix total basé sur quantité et prix unitaire
     */
    private function calculateTotalPrice(): void
    {
        $total = floatval($this->unitPrice) * $this->quantity;
        $this->totalPrice = number_format($total, 2, '.', '');
        $this->calculateTaxAmount();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Calculer le montant des taxes
     */
    private function calculateTaxAmount(): void
    {
        if ($this->taxRate !== null) {
            $taxAmount = (floatval($this->totalPrice) * floatval($this->taxRate)) / 100;
            $this->taxAmount = number_format($taxAmount, 2, '.', '');
        }
    }

    /**
     * Obtenir le prix total TTC
     */
    public function getTotalPriceWithTax(): string
    {
        $totalWithTax = floatval($this->totalPrice) + floatval($this->taxAmount);
        return number_format($totalWithTax, 2, '.', '');
    }

    /**
     * Ajouter une option d'article
     */
    public function addItemOption(string $key, mixed $value): static
    {
        if (!$this->itemOptions) {
            $this->itemOptions = [];
        }
        
        $this->itemOptions[$key] = $value;
        $this->updatedAt = new \DateTimeImmutable();
        
        return $this;
    }

    /**
     * Obtenir une option d'article
     */
    public function getItemOption(string $key): mixed
    {
        return $this->itemOptions[$key] ?? null;
    }

    /**
     * Créer un OrderItem pour un événement
     */
    public static function createForEvent(Event $event, int $quantity = 1, array $options = []): self
    {
        $item = new self();
        $item->setItemType('event_registration');
        $item->setItemId($event->getId());
        $item->setItemName($event->getTitle());
        $item->setItemDescription($event->getShortDescription());
        $item->setQuantity($quantity);
        
        // Prix à définir selon la logique métier
        $item->setUnitPrice('0.00'); // À configurer
        
        if (!empty($options)) {
            $item->setItemOptions($options);
        }
        
        return $item;
    }

    /**
     * Créer un OrderItem pour un accès galerie
     */
    public static function createForGalleryAccess($gallery, array $options = []): self
    {
        $item = new self();
        $item->setItemType('gallery_access');
        $item->setItemId($gallery->getId());
        $item->setItemName('Accès galerie: ' . $gallery->getTitle());
        $item->setItemDescription($gallery->getDescription());
        $item->setQuantity(1);
        
        // Prix à définir selon la logique métier
        $item->setUnitPrice('0.00'); // À configurer
        
        if (!empty($options)) {
            $item->setItemOptions($options);
        }
        
        return $item;
    }

    /**
     * Obtenir le libellé du type d'article
     */
    public function getItemTypeLabel(): string
    {
        return match($this->itemType) {
            'product' => 'Produit',
            'service' => 'Service',
            'event_registration' => 'Inscription événement',
            'gallery_access' => 'Accès galerie',
            default => $this->itemType
        };
    }
}