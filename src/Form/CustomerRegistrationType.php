<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'mapped' => false,
                'attr' => ['placeholder' => 'Enter your full name'],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Please enter your full name',
                    ),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'Your full name must be at least {{ limit }} characters long',
                        maxMessage: 'Your full name cannot be longer than {{ limit }} characters',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'placeholder' => 'you@example.com',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'Please enter your email'
                    ),
                    new Assert\Email(
                        message: 'Please enter a valid email address'
                    ),
                    new Assert\Length(
                        max: 180,
                        maxMessage: 'Email cannot be longer than {{ limit }} characters'
                    ),
                ],
            ])
            ->add('password', RepeatedType::class, [
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
                    new Assert\NotBlank(
                        message: 'Please enter a password'
                    ),
                    new Assert\Length(
                        min: 12,
                        max: 4096,
                        minMessage: 'Password must be at least {{ limit }} characters long',
                    ),
                    new Assert\Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        message: 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'I agree to the Terms of Service and Privacy Policy',
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'You must agree to the terms to continue'
                    ),
                ],
            ]);
    }
}
