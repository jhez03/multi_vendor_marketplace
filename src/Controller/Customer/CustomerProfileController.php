<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\Form\CustomerProfileType;
use App\Service\ProfileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CustomerProfileController
 *
 * Handles viewing and editing the customer's own profile.
 *
 * Security model:
 *   - #[IsGranted('ROLE_CUSTOMER')] ensures only authenticated customers access
 *     these routes. The security.yaml access_control is an additional layer.
 *   - We always fetch the profile from the currently authenticated user,
 *     never from a URL parameter. This prevents horizontal privilege
 *     escalation (user A editing user B's profile).
 *   - Form submission validates CSRF automatically via Symfony's form system.
 *   - File handling is delegated to ProfileUploadService which applies
 *     its own security hardening.
 *
 * Why POST only for edit?
 *   GET requests are bookmarkable, cached, and logged — they should never
 *   mutate state. All mutations go through POST with CSRF protection.
 */
#[IsGranted('ROLE_CUSTOMER')]
#[Route('/customer/profile', name: 'customer_profile_')]
final class CustomerProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly ProfileUploadService    $uploadService,
    ) {}

    /**
     * Show + handle the customer profile edit form.
     *
     * We combine show and edit into one action (PRG pattern):
     *   GET  → render form pre-filled with current values
     *   POST → validate, save, redirect (prevents double-submit on refresh)
     */
    #[Route('', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $profile = $user->getCustomerProfile();

        // Guard: every authenticated customer should have a profile.
        // If somehow they don't, redirect gracefully rather than throwing 500.
        if ($profile === null) {
            $this->addFlash('error', 'Your profile could not be found. Please contact support.');
            return $this->redirectToRoute('customer_dashboard');
        }

        $form = $this->createForm(CustomerProfileType::class, $profile);

        // Pre-fill the unmapped birthDate field from the entity string value
        if ($profile->getBirthDate() !== null) {
            try {
                $dateObj = new \DateTimeImmutable($profile->getBirthDate());
                $form->get('birthDate')->setData($dateObj);
            } catch (\Exception) {
                // Malformed date in DB — silently ignore, let user re-enter
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── Handle avatar upload ──────────────────────────────────────────
            $avatarFile = $form->get('avatarFile')->getData();

            if ($avatarFile !== null) {
                try {
                    $newFilename = $this->uploadService->uploadAvatar(
                        $avatarFile,
                        $profile->getAvatarUrl(),
                    );
                    $profile->setAvatarUrl($newFilename);
                } catch (FileException $e) {
                    // Log and surface a user-friendly message; don't expose the
                    // internal path or exception message to the response.
                    $this->addFlash('error', 'Avatar upload failed. Please try again.');
                    return $this->redirectToRoute('customer_profile_edit');
                }
            }

            // ── Handle birth date (unmapped field) ────────────────────────────
            $birthDate = $form->get('birthDate')->getData();
            if ($birthDate instanceof \DateTimeInterface) {
                $profile->setBirthDate($birthDate->format('Y-m-d'));
            }

            $this->em->flush();

            $this->addFlash('success', 'Profile updated successfully.');

            // PRG: redirect to same page to prevent re-submission on refresh
            return $this->redirectToRoute('customer_profile_edit');
        }

        return $this->render('customer/profile/edit.html.twig', [
            'user'    => $user,
            'profile' => $profile,
            'form'    => $form,
        ]);
    }
}
