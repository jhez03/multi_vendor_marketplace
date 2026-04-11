<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shop::class, inversedBy: 'product')]
    #[ORM\JoinColumn(name: 'shop_id', referencedColumnName: 'id', nullable: false, unique: false)]
    private ?Shop $shop = null;

    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
    private Collection $productImages;

    /**
     * Category is optional (nullable) so existing products don't break.
     * A seller can always assign a category later via the edit form.
     */
    #[ORM\ManyToOne(targetEntity: ProductCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $category = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(enumType: ProductStatus::class)]
    private ?ProductStatus $status = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->productImages = new ArrayCollection();
        $this->status        = ProductStatus::ACTIVE;
        $this->createdAt     = new \DateTimeImmutable();
    }

    // ── Category ─────────────────────────────────────────────────────────────

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    // ── Product images ────────────────────────────────────────────────────────

    public function addProductImage(ProductImage $image): self
    {
        if (!$this->productImages->contains($image)) {
            $this->productImages[] = $image;
            $image->setProduct($this);
        }
        return $this;
    }

    public function getProductImages(): Collection
    {
        return $this->productImages;
    }

    /**
     * Returns image URLs as a plain array — convenient for Twig.
     *
     * @return string[]
     */
    public function getProductImageUrls(): array
    {
        return $this->productImages->map(fn(ProductImage $img) => $img->getUrl())->toArray();
    }

    public function setProductImages(?Collection $productImages): self
    {
        $this->productImages = $productImages;
        return $this;
    }

    // ── Scalar getters / setters ──────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): ?Shop
    {
        return $this->shop;
    }

    public function setShop(Shop $shop): static
    {
        $this->shop = $shop;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): ?ProductStatus
    {
        return $this->status;
    }

    public function setStatus(ProductStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
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
}
