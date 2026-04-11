<?php

namespace App\Form;

use App\Entity\ProductCategory;
use App\Entity\ProductStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductType extends AbstractType
{
    public function __construct(
        /**
         * Injected from the `app.currency` container parameter.
         */
        #[Autowire(param: 'app.currency')]
        private readonly string $currencyCode,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => 'Product name',
                'constraints' => [
                    new Assert\NotBlank(message: 'Product name is required.'),
                    new Assert\Length(
                        min: 3,
                        max: 255,
                        minMessage: 'Name must be at least {{ limit }} characters',
                        maxMessage: 'Name cannot exceed {{ limit }} characters',
                    ),
                ],
                'attr' => ['placeholder' => 'e.g. Wireless Bluetooth Headphones'],
            ])
            ->add('description', TextareaType::class, [
                'label'       => 'Description',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please describe your product'),
                    new Assert\Length(
                        min: 20,
                        max: 5000,
                        minMessage: 'Description must be at least {{ limit }} characters',
                        maxMessage: 'Description cannot exceed {{ limit }} characters',
                    ),
                ],
                'attr' => [
                    'placeholder' => 'Describe your product in detail — features, materials, dimensions…',
                    'rows'        => 5,
                ],
            ])
            ->add('price', MoneyType::class, [
                'label'       => 'Base price',
                'currency'    => $this->currencyCode,
                'constraints' => [
                    new Assert\NotBlank(message: 'Please set a price'),
                    new Assert\Positive(message: 'Price must be greater than zero'),
                ],
                'attr' => ['placeholder' => '0.00'],
            ])
            ->add('status', EnumType::class, [
                'class'        => ProductStatus::class,
                'label'        => 'Listing status',
                'choice_label' => fn(ProductStatus $choice): string => ucfirst($choice->value),
                'placeholder'  => 'Select status',
                'constraints'  => [
                    new Assert\NotBlank(message: 'Please choose a status'),
                ],
            ])
            // ── Category ─────────────────────────────────────────────────────
            // EntityType renders as a <select> and maps directly to the
            // ProductCategory entity on the Product — no manual lookup needed.
            // `required: false` means an uncategorised product is valid.
            ->add('category', EntityType::class, [
                'class'        => ProductCategory::class,
                'label'        => 'Category',
                'required'     => false,
                'placeholder'  => '— No category —',
                'choice_label' => fn(ProductCategory $cat): string => ($cat->getIcon() ? $cat->getIcon() . ' ' : '') . $cat->getName(),
                'query_builder' => fn(\App\Repository\ProductCategoryRepository $repo) => $repo
                    ->createQueryBuilder('c')
                    ->where('c.isActive = :active')
                    ->setParameter('active', true)
                    ->orderBy('c.sortOrder', 'ASC')
                    ->addOrderBy('c.name', 'ASC'),
            ])
            ->add('imageFile', FileType::class, [
                'label'       => 'Product image',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Assert\File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Please upload a JPG, PNG, or WebP image',
                        maxSizeMessage: 'Image must be smaller than 5 MB',
                    ),
                ],
            ]);
    }
}
