<?php

namespace App\Controller\Customer;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Customer Dashboard Controller
 *
 * Why IsGranted here and not only in security.yaml?
 *   security.yaml path-based rules cover URL-level access.
 *   The #[IsGranted] attribute is the "defence in depth" layer — if the
 *   route is ever refactored to a different path the protection travels
 *   with the controller method, not the config file.
 *
 * Why inject OrderRepository instead of using EntityManager directly?
 *   Repository encapsulates query logic, keeps the controller thin, and
 *   makes the dependency explicit in the constructor signature.
 */
#[IsGranted('ROLE_CUSTOMER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    #[Route('/customer/dashboard', name: 'customer_dashboard')]
    public function index(): Response
    {
        /**
         * getUser() is guaranteed non-null here because #[IsGranted] already
         * blocked unauthenticated requests. We cast via @var for IDE/static
         * analysis support — Symfony's UserInterface doesn't expose app methods.
         *
         * @var \App\Entity\User $user
         */
        $user    = $this->getUser();
        $profile = $user->getCustomerProfile();

        /**
         * Fetch the authenticated user's orders, newest first.
         * We pass the user entity directly — the repository joins on the
         * user FK, so no manual ID extraction is needed.
         */
        $orders = $this->orderRepository->findByUser($user->getId());

        /**
         * Pre-compute counts the template needs so we keep logic out of Twig.
         * Twig is for presentation; calculations belong in PHP.
         */
        $completedCount  = 0;
        $inTransitCount  = 0;

        foreach ($orders as $order) {
            $status = $order->getStatus()->value;
            if ($status === 'delivered') {
                ++$completedCount;
            }
            if (in_array($status, ['processing', 'shipped'], true)) {
                ++$inTransitCount;
            }
        }

        return $this->render('customer/dashboard/index.html.twig', [
            'user'           => $user,
            'profile'        => $profile,
            'orders'         => $orders,
            'totalOrders'    => count($orders),
            'completedCount' => $completedCount,
            'inTransitCount' => $inTransitCount,
        ]);
    }
}
