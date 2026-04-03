<?php

namespace App\Entity;

use App\Repository\SellerProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SellerProfileRepository::class)]
class SellerProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $storeName = null;

    #[ORM\Column]
    private ?bool $verified = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $storeDescription = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(mappedBy: 'sellerProfile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStoreName(): ?string
    {
        return $this->storeName;
    }

    public function setStoreName(string $storeName): static
    {
        $this->storeName = $storeName;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getStoreDescription(): ?string
    {
        return $this->storeDescription;
    }

    public function setStoreDescription(string $storeDescription): static
    {
        $this->storeDescription = $storeDescription;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        // unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setSellerProfile(null);
        }

        // set the owning side of the relation if necessary
        if ($user !== null && $user->getSellerProfile() !== $this) {
            $user->setSellerProfile($this);
        }

        $this->user = $user;

        return $this;
    }
}
