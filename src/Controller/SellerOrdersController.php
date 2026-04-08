<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SellerOrdersController extends AbstractController
{
    #[Route('/seller/orders', name: 'app_seller_orders')]
    public function index(): Response
    {
        return $this->render('seller_orders/index.html.twig', [
            'controller_name' => 'SellerOrdersController',
        ]);
    }
}
