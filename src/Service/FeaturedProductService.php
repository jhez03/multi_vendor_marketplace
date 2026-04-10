<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Entity\Product;

final class FeaturedProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * @return Product[]
     */
    public function getHomepageFeatured(int $limit = 6): array
    {
        $featuredProducts = $this->productRepository->findFeaturedFromVerifiedSellers($limit);
        $featuredProducts = array_map(function ($product) {
            $primaryImage = null;
            foreach ($product->getProductImages() as $image) {
                if ($image->isPrimary()) {
                    $primaryImage = $image;
                    break;
                }
            }
            return [
                'id' => $product->getId(),
                'productName' => $product->getName(),
                'storeName' => $product->getShop()->getStoreName(),
                'price' => $product->getPrice(),
                'primaryImageUrl' => $primaryImage?->getUrl(),
            ];
        }, $featuredProducts);

        return $featuredProducts;

    }
}
