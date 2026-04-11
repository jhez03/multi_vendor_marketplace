<?php

declare(strict_types=1);

namespace App\Controller\Seller;

use App\Form\SellerProfileType;
use App\Service\ProfileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Seller ProfileController
 *
 * Security model identical to Customer\ProfileController — always derives
 * profile from the authenticated session, never from URL parameters.
 *
 * Additional concern for sellers: store name is slugified before storage
 * (this matches RegistrationController behaviour). Changing the store name
 * therefore changes the upload subdirectory for new product images going
 * forward, but does NOT retroactively rename existing images — that would
 * be a destructive migration requiring a separate, carefully orchestrated
 * process.
 */
#[IsGranted('ROLE_SELLER')]
#[Route('/seller/profile', name: 'seller_profile_')]
final class SellerProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProfileUploadService   $uploadService,
        private readonly SluggerInterface       $slugger,
    ) {}

    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $profile = $user->getSellerProfile();
        $shop    = $profile?->getStore();

        if ($profile === null) {
            $this->addFlash('error', 'Your seller profile could not be found.');
            return $this->redirectToRoute('seller_dashboard');
        }

        $form = $this->createForm(SellerProfileType::class, $profile);

        // Pre-fill unmapped shop fields from the Shop entity
        if ($shop !== null) {
            $form->get('storeName')->setData($shop->getStoreName());
            $form->get('storeDescription')->setData($shop->getStoreDescription());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── Update shop fields ────────────────────────────────────────────
            if ($shop !== null) {
                // Slugify store name to keep it URL-safe and consistent with
                // how it was set during registration.
                $rawStoreName  = $form->get('storeName')->getData();
                $safeStoreName = $this->slugger->slug((string) $rawStoreName)->toString();
                $shop->setStoreName($safeStoreName);
                $shop->setStoreDescription($form->get('storeDescription')->getData());
            }

            // ── Handle logo upload ────────────────────────────────────────────
            $logoFile = $form->get('logoFile')->getData();

            if ($logoFile !== null) {
                try {
                    $newFilename = $this->uploadService->uploadLogo(
                        $logoFile,
                        $shop?->getLogoUrl(),
                    );
                    $shop?->setLogoUrl($newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Logo upload failed. Please try again.');
                    return $this->redirectToRoute('seller_profile_edit');
                }
            }

            $this->em->flush();

            $this->addFlash('success', 'Seller profile updated successfully.');

            return $this->redirectToRoute('seller_profile_edit');
        }

        return $this->render('seller/profile/edit.html.twig', [
            'user'    => $user,
            'profile' => $profile,
            'shop'    => $shop,
            'form'    => $form,
        ]);
    }
}
