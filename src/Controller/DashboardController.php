<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        if ($this->isGranted('ROLE_SELLER')) {
            return $this->redirectToRoute('seller_dashboard');
        }
        if ($this->isGranted('ROLE_CUSTOMER')) {
            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->render('login/index.html.twig');
    }
    #[Route('/deny', name: 'access_denied')]
    public function deny(): Response
    {
        return $this->render('access_deny/404.html.twig', [
        ]);
    }
}
