<?php

namespace App\Controller;

use App\Entity\BuyerProfile;
use App\Entity\SellerProfile;
use App\Entity\Shop;
use App\Entity\User;
use App\Form\CustomerRegistrationType;
use App\Form\SellerRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;

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


            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));

            $roles = $user->getRoles();
            $user->setRoles($roles);


            $profile = new BuyerProfile();
            $profile->setUser($user);
            $profile->setFullName($form->get('fullName')->getData());
            $user->setEmail($form->get('email')->getData());


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
        SluggerInterface $slugger,
    ): Response {
        $user = new User();
        $form = $this->createForm(SellerRegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()])) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->redirectToRoute('register_customer');
            }

            //set role
            $roles = $user->getRoles();
            $roles[] = 'ROLE_SELLER';
            $user->setRoles($roles);


            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));

            $profile = new SellerProfile();
            //add to customer profile too
            $cprofile = new BuyerProfile();

            $shop = new Shop();
            $profile->setUser($user);
            $cprofile ->setUser($user);
            $profile->setDisplayName($form->get('fullName')->getData());
            $cprofile->setFullName($form->get('fullName')->getData());
            $storeName = $slugger->slug($form->get('storeName')->getData());

            $shop->setSeller($profile);
            $shop->setStoreName($storeName);
            $shop->setStoreDescription($form->get('storeDescription')->getData());
            $user->setEmail($form->get('email')->getData());


            //save
            $em->persist($user);
            $em->persist($profile);
            $em->persist($cprofile);
            $em->persist($shop);
            $em->flush();

            $this->addFlash('success', 'Registration successful! You can now log in.');
            return $this->redirectToRoute('app_login');
        }


        return $this->render('auth/register_seller.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
