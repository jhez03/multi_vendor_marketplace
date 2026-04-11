<?php

namespace App\Service;

use App\Repository\ProductRepository;

final class FeaturedProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * Returns a flat array of featured product data for the homepage.
     *
     * We map to a plain array here so the Twig template has no dependency
     * on the Product entity — no lazy-loading surprises.
     *
     * @return array<int, array{
     *   id: int,
     *   productName: string,
     *   storeName: string,
     *   price: string,
     *   primaryImageUrl: string|null,
     *   categoryName: string|null,
     *   categorySlug: string|null,
     * }>
     */
    public function getHomepageFeatured(int $limit = 6): array
    {
        $featured = $this->productRepository->findFeaturedFromVerifiedSellers($limit);

        return array_map(static function ($product): array {
            // Find the primary image; fall back to the first image if none marked
            $primaryImage = null;
            foreach ($product->getProductImages() as $image) {
                if ($image->isPrimary()) {
                    $primaryImage = $image;
                    break;
                }
            }
            if ($primaryImage === null && $product->getProductImages()->count() > 0) {
                $primaryImage = $product->getProductImages()->first();
            }

            $category = $product->getCategory();

            return [
                'id'             => $product->getId(),
                'productName'    => $product->getName(),
                'storeName'      => $product->getShop()->getStoreName(),
                'price'          => $product->getPrice(),
                'primaryImageUrl' => $primaryImage?->getUrl(),
                'categoryName'   => $category?->getName(),
                'categorySlug'   => $category?->getSlug(),
            ];
        }, $featured);
    }
}
