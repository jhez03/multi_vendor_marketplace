<?php

namespace App\Controller\Customer;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/customer/dashboard', name: 'customer_dashboard')]
    public function index(): Response
    {
        $user = new User();
        if ($this->isGranted('ROLE_SELLER')) {
            return $this->render('customer/dashboard/index.html.twig', [
                'controller_name' => 'Customer/DashboardController',
                'user' => $this->getUser(),
                'profile' => $user->getCustomerProfile(),
            ]);
        }
    }
}
