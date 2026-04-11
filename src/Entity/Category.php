<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a product category.
 *
 * Categories are hierarchical: a category can have a parent and multiple children.
 * Only leaf categories (no children) should be assigned to products, but the
 * repository enforces this at query level rather than at the DB constraint level
 * to keep migrations simple.
 *
 * Slug rules:
 *   - Unique across the whole table.
 *   - Generated from the name on create; never auto-regenerated on edit to
 *     preserve stable URLs.
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'category')]
#[ORM\Index(columns: ['slug'], name: 'idx_category_slug')]
#[ORM\Index(columns: ['is_active'], name: 'idx_category_active')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Self-referencing parent — null means this is a root/top-level category.
     */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Category $parent = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', cascade: ['persist'])]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $children;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category')]
    private Collection $products;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Category name is required.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Name must be at least {{ limit }} characters.',
        maxMessage: 'Name cannot exceed {{ limit }} characters.',
    )]
    private string $name = '';

    /**
     * URL-safe identifier, e.g. "electronics-computers".
     * Unique across the entire category tree.
     */
    #[ORM\Column(length: 120, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $description = null;

    /**
     * Emoji or icon class string for display purposes, e.g. "💻" or "fa-laptop".
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    /**
     * Controls visibility in the storefront.  Admin can deactivate without deleting.
     */
    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Display order within siblings. Lower value = shown first.
     */
    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->children  = new ArrayCollection();
        $this->products  = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Business logic helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns true when this category has no children (a leaf node).
     * Products should only be assigned to leaf categories.
     */
    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    /**
     * Returns true when this category has no parent (a root node).
     */
    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    /**
     * Returns the full breadcrumb path as a list of names, e.g.
     * ["Electronics", "Computers", "Laptops"].
     *
     * @return list<string>
     */
    public function getBreadcrumb(): array
    {
        $path = [$this->name];
        $node = $this->parent;

        while ($node !== null) {
            array_unshift($path, $node->getName());
            $node = $node->getParent();
        }

        return $path;
    }

    /**
     * Returns the full breadcrumb as a single string, e.g.
     * "Electronics > Computers > Laptops".
     */
    public function getBreadcrumbString(string $separator = ' > '): string
    {
        return implode($separator, $this->getBreadcrumb());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Doctrine lifecycle
    // ──────────────────────────────────────────────────────────────────────────

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Getters / Setters
    // ──────────────────────────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /** @return Collection<int, Category> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Category $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Category $child): static
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
