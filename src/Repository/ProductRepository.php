<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductCategory;
use App\Entity\ProductStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Featured active products from verified sellers for the homepage.
     *
     * @return Product[]
     */
    public function findFeaturedFromVerifiedSellers(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.shop', 'shop')
            ->innerJoin('shop.seller', 'seller')
            ->leftJoin('p.productImages', 'pi')
            ->leftJoin('p.category', 'cat')
            ->addSelect('shop', 'seller', 'pi', 'cat')
            ->andWhere('p.status = :status')
            ->andWhere('seller.isVerified = :verified')
            ->setParameter('status', ProductStatus::ACTIVE)
            ->setParameter('verified', true)
            ->distinct()
            ->addOrderBy('pi.isPrimary', 'DESC')
            ->addOrderBy('pi.sortOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Browse all active products, optionally filtered by category.
     *
     * @return Product[]
     */
    public function findActive(?ProductCategory $category = null, int $limit = 48): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.shop', 'shop')
            ->leftJoin('p.productImages', 'pi')
            ->leftJoin('p.category', 'cat')
            ->addSelect('shop', 'pi', 'cat')
            ->where('p.status = :status')
            ->setParameter('status', ProductStatus::ACTIVE)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($category !== null) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Full-text search across name, description, and store name.
     * Optionally scoped to a single category.
     *
     * Security: all user input is bound as named parameters — never
     * interpolated into the query string.
     *
     * @return Product[]
     */
    public function search(string $query, ?ProductCategory $category = null, int $limit = 24): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $query));

        if ($clean === '' || mb_strlen($clean) > 200) {
            return [];
        }

        $like = '%' . addcslashes($clean, '%_') . '%';

        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.shop', 's')
            ->leftJoin('p.category', 'cat')
            ->addSelect('s', 'cat')
            ->where('p.status = :status')
            ->andWhere(
                'LOWER(p.name)        LIKE LOWER(:like) OR
                 LOWER(p.description) LIKE LOWER(:like) OR
                 LOWER(s.storeName)   LIKE LOWER(:like)'
            )
            ->setParameter('status', ProductStatus::ACTIVE)
            ->setParameter('like', $like)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($category !== null) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->getQuery()->getResult();
    }
}
