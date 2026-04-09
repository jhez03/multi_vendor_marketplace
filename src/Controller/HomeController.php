<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        //check if user has role customer or seller
        if ($this->isGranted('ROLE_CUSTOMER')) {
            $redirect_url = $this->generateUrl('customer_dashboard');
        }
        if ($this->isGranted('ROLE_SELLER')) {
            $redirect_url = $this->generateUrl('seller_dashboard');
        }
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'user' => $this->getUser(),
        ]);
    }
}
