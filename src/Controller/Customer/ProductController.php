<?php

namespace App\Controller\Customer;

use App\Entity\Product;
use App\Entity\ProductStatus;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products_browse')]
    public function browse(Request $request, ProductRepository $repo): Response
    {
        $products = $repo->findBy(
            ['status' => ProductStatus::ACTIVE],
            ['createdAt' => 'DESC']
        );

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('customer/product/browse.html.twig', [
            'user'     => $user,
            'profile'  => $user?->getCustomerProfile(),
            'products' => $products,
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_detail', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        if ($product->getStatus() !== ProductStatus::ACTIVE) {
            throw $this->createNotFoundException('This product is not available.');
        }

        $shop            = $product->getShop();
        $relatedProducts = [];
        /** @var \App\Entity\User $user */
        $user = $this->getUser();


        return $this->render('customer/product/show.html.twig', [
            'user'            => $user,
            'profile'         => $user?->getCustomerProfile(),
            'product'         => $product,
            'shop'            => $shop,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
