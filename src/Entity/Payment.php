<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Order::class, inversedBy: 'payment')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    /** 'stripe' | 'paypal' */
    #[ORM\Column(length: 20)]
    private ?string $provider = null;

    /** Stripe: PaymentIntent ID (pi_xxx)  |  PayPal: Order ID */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $providerTransactionId = null;

    /** Stripe: client_secret for the front-end  |  PayPal: approval link */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $providerClientSecret = null;

    #[ORM\Column(enumType: PaymentStatus::class)]
    private ?PaymentStatus $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    /** ISO 4217, e.g. 'PHP', 'USD' */
    #[ORM\Column(length: 3)]
    private ?string $currency = 'PHP';

    /** Raw webhook/callback payload for debugging */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawPayload = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->status    = PaymentStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function getProvider(): ?string
    {
        return $this->provider;
    }
    public function setProvider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getProviderTransactionId(): ?string
    {
        return $this->providerTransactionId;
    }
    public function setProviderTransactionId(?string $id): static
    {
        $this->providerTransactionId = $id;
        return $this;
    }

    public function getProviderClientSecret(): ?string
    {
        return $this->providerClientSecret;
    }
    public function setProviderClientSecret(?string $secret): static
    {
        $this->providerClientSecret = $secret;
        return $this;
    }

    public function getStatus(): ?PaymentStatus
    {
        return $this->status;
    }
    public function setStatus(PaymentStatus $status): static
    {
        $this->status    = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }
    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getRawPayload(): ?array
    {
        return $this->rawPayload;
    }
    public function setRawPayload(?array $rawPayload): static
    {
        $this->rawPayload = $rawPayload;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
