<?php

namespace App\Controller\Customer;

use App\Entity\Product;
use App\Entity\ProductStatus;
use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    /**
     * GET /products?category=electronics
     *
     * Browsing accepts an optional `category` slug to filter results.
     * No authentication required — this is a public page.
     */
    #[Route('/products', name: 'app_products_browse')]
    public function browse(
        Request $request,
        ProductRepository $productRepo,
        ProductCategoryRepository $categoryRepo,
    ): Response {
        $categorySlug = mb_substr(trim((string) $request->query->get('category', '')), 0, 120);

        // Resolve active category — null means "show all"
        $activeCategory = $categorySlug !== '' ? $categoryRepo->findActiveBySlug($categorySlug) : null;

        // findActive handles the NULL case: no category = all active products
        $products = $productRepo->findActive($activeCategory);

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        return $this->render('customer/product/browse.html.twig', [
            'user'           => $user,
            'profile'        => $user?->getCustomerProfile(),
            'products'       => $products,
            'categories'     => $categoryRepo->findActive(),
            'activeCategory' => $activeCategory,
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        if ($product->getStatus() !== ProductStatus::ACTIVE) {
            throw $this->createNotFoundException('This product is not available.');
        }

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        return $this->render('customer/product/show.html.twig', [
            'user'            => $user,
            'profile'         => $user?->getCustomerProfile(),
            'product'         => $product,
            'shop'            => $product->getShop(),
            'relatedProducts' => [],
        ]);
    }
}
