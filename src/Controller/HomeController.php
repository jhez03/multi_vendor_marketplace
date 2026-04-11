<?php

namespace App\Controller;

use App\Repository\ProductCategoryRepository;
use App\Service\FeaturedProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        FeaturedProductService $featuredProductService,
        ProductCategoryRepository $categoryRepo,
    ): Response {
        return $this->render('home/index.html.twig', [
            'user'             => $this->getUser(),
            'featuredProducts' => $featuredProductService->getHomepageFeatured(),
            // Only categories that actually have active products — avoids
            // showing empty buckets on the homepage chips.
            'categories'       => $categoryRepo->findActiveWithProducts(),
        ]);
    }
}
