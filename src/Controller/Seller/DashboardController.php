<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Seller Dashboard Controller
 *
 * Guard strategy:
 *   We use the custom SellerVoter (SELLER_ACCESS) rather than a bare
 *   ROLE_SELLER check. The voter adds an extra layer: it verifies the
 *   seller profile exists AND is verified. Admins pass automatically.
 *
 *   If verification is pending the voter denies access and Symfony
 *   redirects to the configured access_denied_url (/deny).
 *
 * Why separate queries per stat instead of one big JOIN?
 *   A single JOIN for products + orders + revenue would produce a
 *   cartesian product. Three focused queries are cheaper, clearer,
 *   and easier to cache individually later.
 */
#[IsGranted('SELLER_ACCESS')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository    $productRepository,
        private readonly OrderRepository      $orderRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/seller/dashboard', name: 'seller_dashboard')]
    public function index(): Response
    {
        /**
         * Guaranteed non-null by #[IsGranted('SELLER_ACCESS')] above.
         *
         * @var \App\Entity\User $user
         */
        $user    = $this->getUser();
        $profile = $user->getSellerProfile();
        $shop    = $profile?->getStore();

        // ── Product stats ──────────────────────────────────────────────────
        // All products belonging to this seller's shop, newest first.
        $products = $shop
            ? $this->productRepository->findBy(
                ['shop' => $shop],
                ['createdAt' => 'DESC']
            )
            : [];

        $activeProductCount = 0;
        foreach ($products as $p) {
            if ($p->getStatus()->value === 'active') {
                ++$activeProductCount;
            }
        }

        // ── Order stats ────────────────────────────────────────────────────
        // Orders that contain at least one item from this shop.
        // We use a DQL sub-query so we don't load the entire order graph.
        $sellerOrders = $shop ? $this->getSellerOrders($shop->getId()) : [];

        $totalOrderCount   = count($sellerOrders);
        $pendingOrderCount = 0;
        $grossRevenue      = 0.0;

        foreach ($sellerOrders as $order) {
            $status = $order->getStatus()->value;
            if (in_array($status, ['awaiting_payment', 'processing', 'shipped'], true)) {
                ++$pendingOrderCount;
            }
            // Only count revenue for paid/shipped/delivered orders
            if (in_array($status, ['paid', 'processing', 'shipped', 'delivered'], true)) {
                $grossRevenue += (float) $order->getTotal();
            }
        }

        // ── Recent 5 orders for the dashboard table ────────────────────────
        $recentOrders = array_slice($sellerOrders, 0, 5);

        return $this->render('seller/dashboard/index.html.twig', [
            'user'               => $user,
            'profile'            => $profile,
            'shop'               => $shop,
            'products'           => $products,

            // Stats used by the 4-card grid
            'grossRevenue'       => $grossRevenue,
            'totalOrderCount'    => $totalOrderCount,
            'activeProductCount' => $activeProductCount,
            'pendingOrderCount'  => $pendingOrderCount,

            // Table
            'recentOrders'       => $recentOrders,
        ]);
    }

    /**
     * Returns orders that contain at least one item belonging to this shop.
     *
     * We use a sub-query (EXISTS) instead of a JOIN to avoid row duplication
     * when an order has multiple items from the same shop.
     *
     * @return \App\Entity\Order[]
     */
    private function getSellerOrders(int $shopId): array
    {
        return $this->em->createQuery(
            'SELECT o FROM App\Entity\Order o
             WHERE EXISTS (
                 SELECT 1 FROM App\Entity\OrderItem oi
                 JOIN oi.product p
                 WHERE oi.order = o
                   AND p.shop = :shopId
             )
             ORDER BY o.createdAt DESC'
        )
            ->setParameter('shopId', $shopId)
            ->getResult();
    }
}
