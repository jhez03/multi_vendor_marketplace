<?php

namespace App\Controller\Seller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/seller/dashboard', name: 'seller_dashboard')]
    public function index(): Response
    {

        $user = new User();

        return $this->render('seller/dashboard/index.html.twig', [
            'controller_name' => 'Seller/DashboardController',
            'user' => $user,
            'profile' => $user->getSellerProfile(),
        ]);
    }
    #[Route('/seller/pending', name: 'seller_deny')]
    public function deny(): Response
    {
        return $this->render('seller/deny.html.twig', [
        ]);
    }
}
