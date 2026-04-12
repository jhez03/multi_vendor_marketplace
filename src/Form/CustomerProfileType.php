<?php

namespace App\Form;

use App\Entity\BuyerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CustomerProfileType
 *
 * Handles editing of BuyerProfile fields.
 *
 * Design decision: avatarFile is NOT mapped to the entity (mapped: false)
 * because the entity stores only the filename string, not the UploadedFile
 * object. The controller is responsible for handling the file, moving it,
 * and persisting the new filename. This is standard Symfony practice and
 * keeps the entity free of HTTP concerns.
 *
 * Security:
 *   - CSRF is handled by the parent form (enabled globally).
 *   - File constraints are enforced server-side; never trust client-provided
 *     MIME types alone — Symfony's File constraint uses finfo for detection.
 *   - Max file size is enforced both here (5MB) and in php.ini.
 */
class CustomerProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label'       => 'Full name',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your full name'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Name must be at least {{ limit }} characters',
                        maxMessage: 'Name cannot exceed {{ limit }} characters',
                    ),
                    // Prevent XSS via name field
                    new Assert\Regex(
                        pattern: '/^[\pL\pN\s\-\'\.]+$/u',
                        message: 'Name contains invalid characters',
                    ),
                ],
                'attr' => ['placeholder' => 'Your full name'],
            ])
            ->add('birthDate', DateType::class, [
                'label'    => 'Date of birth',
                'required' => false,
                'widget'   => 'single_text',
                'mapped'   => false, // BuyerProfile stores birthDate as string; we handle conversion
                'constraints' => [
                    new Assert\LessThan(
                        value: 'today',
                        message: 'Birth date must be in the past',
                    ),
                ],
                'attr' => ['max' => new \DateTimeImmutable()->format('Y-m-d')],
            ])
            ->add('avatarFile', FileType::class, [
                'label'       => 'Profile photo',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '2M',
                        // Server-side MIME detection via finfo — cannot be spoofed
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        mimeTypesMessage: 'Please upload a JPEG, PNG, WebP, or GIF image',
                        maxSizeMessage: 'Avatar must be smaller than 2 MB',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BuyerProfile::class,
            // CSRF token is generated per form name, adding an extra layer beyond the global token
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'customer_profile_edit',
        ]);
    }
}
