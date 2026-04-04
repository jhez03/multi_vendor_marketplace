<?php

namespace App\Controller;

use App\Entity\CustomerProfile;
use App\Entity\SellerProfile;
use App\Entity\User;
use App\Form\CustomerRegistrationType;
use App\Form\SellerRegistrationFormType;
use App\Form\SellerRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class RegistrationController extends AbstractController
{
    #[Route('register/customer', name: 'register_customer')]
    public function registerCustomer(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(CustomerRegistrationType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            if ($em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('register_customer');
            }
            //set Role
            $user->setRoles(['ROLE_CUSTOMER']);

            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $profile = new CustomerProfile();
            $profile->setUser($user);
            $profile->setFullName($form->get('fullName')->getData());

            //save
            $em->persist($user);
            $em->persist($profile);
            $em->flush();

            $this->addFlash('success', 'Registration successful! You can now log in.');
            return $this->redirectToRoute('app_login');
        }


        return $this->render('auth/register_customer.html.twig', [
            'registrationForm' => $form,
        ]);
    }
    #[Route('register/seller', name: 'register_seller')]
    public function registerSeller(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(SellerRegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('register_seller');
            }
            //set Role
            $user->setRoles(['ROLE_SELLER']);

            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));

            $seller = new SellerProfile();
            $seller->setUser($user);
            $seller->setStoreName($form->get('storeName')->getData());

            $seller->setVerified(false);

            //save
            $em->persist($user);
            $em->persist($seller);
            $em->flush();

            $this->addFlash('success', 'Seller Registered successfully! Your account is pending verification by the admin. You will be notified once your account is verified.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register_customer.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
