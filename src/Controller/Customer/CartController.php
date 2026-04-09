<?php

namespace App\Controller\Customer;

use App\Entity\Product;
use App\Entity\ProductStatus;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart', name: 'app_cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── View ────────────────────────────────────────────────────

    #[Route('', name: 'index')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user*/
        $user = $this->getUser();
        return $this->render('customer/cart/index.html.twig', [
            'user'      => $user,
            'profile'   => $user?->getCustomerProfile(),
            'cart'      => $this->cartService->getCart(),
            'subtotal'  => $this->cartService->getSubtotal(),
            'itemCount' => $this->cartService->getTotalQuantity(),
        ]);
    }

    // ── Mutations ───────────────────────────────────────────────

    #[Route('/add/{id}', name: 'add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function add(Product $product, Request $request): Response
    {
        if ($product->getStatus() !== ProductStatus::ACTIVE) {
            $this->addFlash('error', 'This product is not available.');
            return $this->redirect($request->headers->get('referer', '/'));
        }

        $qty = max(1, (int) $request->request->get('quantity', 1));
        $this->cartService->add($product, $qty);

        $this->addFlash('success', '"' . $product->getName() . '" added to your cart.');

        // Turbo-friendly: redirect back to referrer or product page
        return $this->redirect($request->headers->get('referer', '/'));
    }

    #[Route('/update/{productId}', name: 'update', methods: ['POST'], requirements: ['productId' => '\d+'])]
    public function update(int $productId, Request $request): Response
    {
        $qty = (int) $request->request->get('quantity', 0);
        $this->cartService->update($productId, $qty);

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{productId}', name: 'remove', methods: ['POST'], requirements: ['productId' => '\d+'])]
    public function remove(int $productId): Response
    {
        $this->cartService->remove($productId);
        $this->addFlash('success', 'Item removed from cart.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(): Response
    {
        $this->cartService->clear();
        return $this->redirectToRoute('app_cart_index');
    }

    // ── API (used by Stimulus for live count badge) ─────────────

    #[Route('/count', name: 'count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        return $this->json(['count' => $this->cartService->getTotalQuantity()]);
    }
}
