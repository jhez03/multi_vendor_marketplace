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

        //check if no access controll
        if (!$this->isGranted('ROLE_SELLER')) {
            return $this->redirectToRoute('app_dashboard');
        }
        $user = new User();

        return $this->render('seller/dashboard/index.html.twig', [
            'controller_name' => 'Seller/DashboardController',
            'user' => $user,
            'profile' => $user->getSellerProfile(),
        ]);
    }
    #[Route('/deny', name: 'access_denied')]
    public function deny(): Response
    {
        return $this->render('access_deny/404.html.twig', [
        ]);
    }
}
