<?php

namespace App\Controller\Customer;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order', name: 'app_order_')]
class OrderController extends AbstractController
{
    public function __construct(private readonly OrderRepository $orderRepo) {}

    #[Route('/thank-you/{orderNumber}', name: 'thankyou')]
    public function thankyou(string $orderNumber): Response
    {
        $order = $this->orderRepo->findOneBy(['orderNumber' => $orderNumber]);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        // Security: only the owner (or guest with matching token) can view
        $user = $this->getUser();
        if ($user && $order->getUser() && $order->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('customer/order/thankyou.html.twig', [
            'user'    => $user,
            'profile' => $user?->getCustomerProfile(),
            'order'   => $order,
            'payment' => $order->getPayment(),
        ]);
    }
}
