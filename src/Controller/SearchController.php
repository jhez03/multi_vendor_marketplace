<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    /**
     * GET /search?q=...
     *
     * Accepts both normal browser requests and Turbo Frame requests
     * (header `Turbo-Frame: search-results`). Returns the full page for normal
     * requests so that direct URL navigation works correctly, and Turbo handles
     * the frame swap automatically — no extra code needed.
     *
     * Security notes:
     *  • Query is length-capped at 200 characters before it reaches the DB.
     *  • HTML-escaping is Twig's default; no raw output.
     *  • No authentication required — search is public.
     */
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request, ProductRepository $repo): Response
    {
        $raw   = $request->query->get('q', '');

        // Server-side validation: strip control chars, limit length
        $query = mb_substr(trim((string) $raw), 0, 200);

        $products = [];
        $searched = false;

        if ($query !== '') {
            $searched = true;
            $products = $repo->search($query);
        }

        return $this->render('search/results.html.twig', [
            'query'    => $query,
            'products' => $products,
            'searched' => $searched,
            'user'     => $this->getUser(),
        ]);
    }
}
