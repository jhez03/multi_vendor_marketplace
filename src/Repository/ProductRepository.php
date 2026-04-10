<?php

namespace App\Repository;

use App\Entity\Product;
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
    * @return Product[]
    */
    public function findFeaturedFromVerifiedSellers(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('p')
           ->innerJoin('p.shop', 'shop')
           ->innerJoin('shop.seller', 'seller')
           ->leftJoin('p.productImages', 'pi')
           ->addSelect('shop', 'seller', 'pi')
           ->andWhere('p.status = :status')
           ->andWhere('seller.isVerified = :verified')
           ->setParameter('status', ProductStatus::ACTIVE)
           ->setParameter('verified', true)
           ->distinct()
           // Prefer primary image first inside the collection (best-effort).
           // This does NOT filter out non-primary images; it only affects ordering.
           ->addOrderBy('pi.isPrimary', 'DESC')
           ->addOrderBy('pi.sortOrder', 'ASC')
           ->addOrderBy('p.createdAt', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
    /**
    * Search active products by name, description, or store name.
    *
    * Security: All user input is passed as named DQL parameters — never
    * interpolated into the query string — so SQL injection is impossible.
    *
    * @return Product[]
    */
    public function search(string $query, int $limit = 24): array
    {
        // Sanitize: trim whitespace, collapse multiple spaces
        $clean = trim(preg_replace('/\s+/', ' ', $query));

        // Reject empty / overly long inputs early (secondary guard; controller
        // already validates, but defense-in-depth is worth it here).
        if ($clean === '' || mb_strlen($clean) > 200) {
            return [];
        }

        // Wrap for LIKE — escape literal % and _ so users can't craft wildcards
        $like = '%' . addcslashes($clean, '%_') . '%';

        return $this->createQueryBuilder('p')
            ->innerJoin('p.shop', 's')
            ->addSelect('s')
            ->where('p.status = :status')
            ->andWhere(
                'LOWER(p.name)        LIKE LOWER(:like) OR
                 LOWER(p.description) LIKE LOWER(:like) OR
                 LOWER(s.storeName)   LIKE LOWER(:like)'
            )
            ->setParameter('status', ProductStatus::ACTIVE)
            ->setParameter('like', $like)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
