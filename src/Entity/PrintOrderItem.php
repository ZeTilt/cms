<?php

namespace App\Entity;

use App\Repository\PrintOrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrintOrderItemRepository::class)]
class PrintOrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrintOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private PrintOrder $printOrder;

    #[ORM\ManyToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Image $image;

    #[ORM\Column(length: 50)]
    private string $printFormat; // 10x15, 13x18, 20x30, etc.

    #[ORM\Column(length: 20)]
    private string $paperType = 'standard'; // standard, premium, canvas, etc.

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalPrice = '0.00';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $ceweProductData = null; // Données spécifiques CEWE pour ce produit

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrintOrder(): PrintOrder
    {
        return $this->printOrder;
    }

    public function setPrintOrder(PrintOrder $printOrder): static
    {
        $this->printOrder = $printOrder;
        return $this;
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function setImage(Image $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getPrintFormat(): string
    {
        return $this->printFormat;
    }

    public function setPrintFormat(string $printFormat): static
    {
        $this->printFormat = $printFormat;
        return $this;
    }

    public function getPaperType(): string
    {
        return $this->paperType;
    }

    public function setPaperType(string $paperType): static
    {
        $this->paperType = $paperType;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->updateTotalPrice();
        return $this;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        $this->updateTotalPrice();
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

    private function updateTotalPrice(): void
    {
        $this->totalPrice = bcmul($this->unitPrice, (string)$this->quantity, 2);
    }

    public function getCeweProductData(): ?array
    {
        return $this->ceweProductData;
    }

    public function setCeweProductData(?array $ceweProductData): static
    {
        $this->ceweProductData = $ceweProductData;
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

    public function getFormattedUnitPrice(): string
    {
        return number_format(floatval($this->unitPrice), 2) . ' €';
    }

    public function getFormattedTotalPrice(): string
    {
        return number_format(floatval($this->totalPrice), 2) . ' €';
    }

    public function getDisplayName(): string
    {
        return sprintf(
            '%s - %s (%s) x%d',
            $this->image->getOriginalName(),
            $this->printFormat,
            $this->paperType,
            $this->quantity
        );
    }
}