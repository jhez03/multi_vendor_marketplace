<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    /** Guest identifier when user is not logged in */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $guestToken = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    private ?OrderStatus $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $subtotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    /** Shipping address snapshot */
    #[ORM\Column(type: Types::JSON)]
    private array $shippingAddress = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $items;

    #[ORM\OneToOne(targetEntity: Payment::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->items     = new ArrayCollection();
        $this->status    = OrderStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orderNumber = strtoupper('ORD-' . bin2hex(random_bytes(5)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getGuestToken(): ?string
    {
        return $this->guestToken;
    }
    public function setGuestToken(?string $guestToken): static
    {
        $this->guestToken = $guestToken;
        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }
    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }
    public function setStatus(OrderStatus $status): static
    {
        $this->status    = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSubtotal(): ?string
    {
        return $this->subtotal;
    }
    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }
    public function setTotal(string $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }
    public function setShippingAddress(array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getPaymentProvider(): ?string
    {
        return $this->paymentProvider;
    }
    public function setPaymentProvider(?string $paymentProvider): static
    {
        $this->paymentProvider = $paymentProvider;
        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }
    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** Total in cents for payment APIs */
    public function getTotalInCents(): int
    {
        return (int) round((float) $this->total * 100);
    }
}
