<?php

namespace App\Form;

use App\Entity\SellerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SellerProfileType
 *
 * Handles editing of both the SellerProfile (display name) and the
 * associated Shop (store name, description, logo) in a single form.
 *
 * Design decision: shop fields are added as unmapped fields (mapped: false)
 * because they belong to a different entity (Shop). The controller reads
 * these values directly from the submitted form and applies them to the
 * correct entity. Alternatively, a CollectionType or embedded form could
 * be used, but for a small number of fields, unmapped is simpler and avoids
 * complex data transformation.
 *
 * Security: same CSRF and file validation approach as CustomerProfileType.
 */
class SellerProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label'       => 'Your display name',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your display name'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Display name must be at least {{ limit }} characters',
                        maxMessage: 'Display name cannot exceed {{ limit }} characters',
                    ),
                    new Assert\Regex(
                        pattern: '/^[\pL\pN\s\-\'\.]+$/u',
                        message: 'Display name contains invalid characters',
                    ),
                ],
                'attr' => ['placeholder' => 'Your public name'],
            ])
            ->add('storeName', TextType::class, [
                'label'   => 'Store name',
                'mapped'  => false,   // belongs to Shop entity
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your store name'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Store name must be at least {{ limit }} characters',
                        maxMessage: 'Store name cannot exceed {{ limit }} characters',
                    ),
                ],
                'attr' => ['placeholder' => 'My Awesome Store'],
            ])
            ->add('storeDescription', TextareaType::class, [
                'label'    => 'Store description',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new Assert\Length(
                        max: 2000,
                        maxMessage: 'Description cannot exceed {{ limit }} characters',
                    ),
                ],
                'attr' => [
                    'placeholder' => 'Tell buyers about your store…',
                    'rows'        => 4,
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label'    => 'Store logo',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Please upload a JPEG, PNG, or WebP image',
                        maxSizeMessage: 'Logo must be smaller than 2 MB',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => SellerProfile::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'seller_profile_edit',
        ]);
    }
}
