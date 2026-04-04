<?php

namespace App\Form;

use App\Entity\SellerProfile;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SellerRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Business email address',
                'attr' => [
                    'placeholder' => 'you@yourstore.com',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your email'),
                    new Assert\Email(message: 'Please enter a valid email address'),
                    new Assert\Length(max: 180, maxMessage: 'Email cannot be longer than {{ limit }} characters'),
                ],
            ])
            ->add('storeName', TextType::class, [
                'label' => 'Store name',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'My Awesome Store',
                    'autocomplete' => 'organization',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter your store name'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Store name must be at least {{ limit }} characters',
                        maxMessage: 'Store name cannot be longer than {{ limit }} characters',
                    ),
                ],
            ])
            ->add('storeDescription', TextareaType::class, [
                'label' => 'Store description',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Tell customers what you sell and what makes your store unique...',
                    'rows' => 4,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a store description'),
                    new Assert\Length(
                        min: 20,
                        max: 2000,
                        minMessage: 'Description must be at least {{ limit }} characters',
                        maxMessage: 'Description cannot be longer than {{ limit }} characters',
                    ),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => 'Create a strong password',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm password',
                    'attr' => [
                        'placeholder' => 'Repeat your password',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please enter a password'),
                    new Assert\Length(
                        min: 12,
                        max: 4096,
                        minMessage: 'Password must be at least {{ limit }} characters',
                    ),
                    new Assert\Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        message: 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'I agree to the Terms of Service, Privacy Policy, and Seller Agreement',
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(message: 'You must agree to the terms to continue'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SellerProfile::class,
        ]);
    }
}
