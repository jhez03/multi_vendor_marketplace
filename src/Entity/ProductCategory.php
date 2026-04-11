<?php

namespace App\Entity;

use App\Repository\ProductCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProductCategory
 *
 * A flat category taxonomy used to organise products.
 * We keep it intentionally flat (no parent/child tree) for now —
 * a self-referential tree can be added later without a breaking migration.
 *
 * Slug is the URL-safe identifier used in routes, e.g. "home-living".
 * It must be unique and is generated from the name on creation.
 */
#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
#[ORM\Table(name: 'product_category')]
#[ORM\UniqueConstraint(name: 'UNIQ_CAT_SLUG', columns: ['slug'])]
class ProductCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Human-readable label shown in the UI, e.g. "Home & Living".
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    /**
     * URL-safe slug generated from name, e.g. "home-living".
     * Stored so routes stay stable even if the name is edited.
     */
    #[ORM\Column(length: 120)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-z0-9-]+$/')]
    private ?string $slug = null;

    /**
     * Optional short description shown on the category browse page.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /**
     * Emoji or icon identifier for the category card (e.g. "💻", "👕").
     * Kept as a simple string so it can be swapped for an icon class later.
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $icon = null;

    /**
     * Lower numbers appear first. Allows manual ordering without touching slugs/names.
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    /**
     * Whether the category is shown publicly.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->products  = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Returns the count of active products in this category.
     * Used in browse pages without extra queries when products are eager-loaded.
     */
    public function getActiveProductCount(): int
    {
        return $this->products->filter(
            static fn(Product $p): bool => $p->getStatus() === ProductStatus::ACTIVE
        )->count();
    }
}
