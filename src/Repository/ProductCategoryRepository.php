<?php

namespace App\Repository;

use App\Entity\ProductCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductCategory>
 */
class ProductCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCategory::class);
    }

    /**
     * Returns all active categories ordered by sortOrder then name.
     * Used in navigation, homepage chips, and the product form dropdown.
     *
     * @return ProductCategory[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns one active category by slug.
     * Slug is used in URLs so it is the canonical lookup key.
     */
    public function findActiveBySlug(string $slug): ?ProductCategory
    {
        return $this->createQueryBuilder('c')
            ->where('c.slug = :slug')
            ->andWhere('c.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns active categories that have at least one active product.
     * Useful for the homepage category chips — no empty buckets.
     *
     * @return ProductCategory[]
     */
    public function findActiveWithProducts(): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.products', 'p')
            ->where('c.isActive = :active')
            ->andWhere('p.status = :status')
            ->setParameter('active', true)
            ->setParameter('status', \App\Entity\ProductStatus::ACTIVE)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }
}
