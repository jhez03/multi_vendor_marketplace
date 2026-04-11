<?php

namespace App\Controller;

use App\Repository\ProductCategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    /**
     * GET /search?q=...&category=electronics
     *
     * - `q` is optional — empty q with a category shows all products in that category.
     * - `category` is an optional slug that scopes results to one category.
     * - Both parameters are length-capped before reaching the DB.
     * - HTML-escaping is Twig's default; no raw output.
     */
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(
        Request $request,
        ProductRepository $productRepo,
        ProductCategoryRepository $categoryRepo,
    ): Response {
        $raw          = $request->query->get('q', '');
        $categorySlug = mb_substr(trim((string) $request->query->get('category', '')), 0, 120);

        // Server-side validation: strip control chars, limit length
        $query = mb_substr(trim((string) $raw), 0, 200);

        // Resolve category — null if slug is empty or not found
        $category = $categorySlug !== '' ? $categoryRepo->findActiveBySlug($categorySlug) : null;

        $products = [];
        $searched = false;

        if ($query !== '' || $category !== null) {
            $searched = true;

            if ($query !== '') {
                $products = $productRepo->search($query, $category);
            } else {
                // Category-only browse — show all active products in that category
                $products = $productRepo->findActive($category);
            }
        }

        return $this->render('search/results.html.twig', [
            'query'      => $query,
            'products'   => $products,
            'searched'   => $searched,
            'category'   => $category,
            'categories' => $categoryRepo->findActive(),
            'user'       => $this->getUser(),
        ]);
    }
}
