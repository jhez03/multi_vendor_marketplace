<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    /** Nullable — product may be deleted later, but the order snapshot lives on */
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    /** Snapshot fields — frozen at purchase time */
    #[ORM\Column(length: 255)]
    private ?string $productName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $lineTotal = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }
    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }
    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }
    public function setProductName(string $productName): static
    {
        $this->productName = $productName;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }
    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }
    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getLineTotal(): ?string
    {
        return $this->lineTotal;
    }
    public function setLineTotal(string $lineTotal): static
    {
        $this->lineTotal = $lineTotal;
        return $this;
    }
}
