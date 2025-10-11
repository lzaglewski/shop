<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Model\ProductCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\All;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.product_name_required',
                    ]),
                ],
            ])
            ->add('sku', TextType::class, [
                'label' => 'form.sku',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.sku_required',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('basePrice', MoneyType::class, [
                'label' => 'form.base_price',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.base_price_required',
                    ]),
                    new Positive([
                        'message' => 'form.price_positive',
                    ]),
                ],
            ])
            ->add('stock', NumberType::class, [
                'label' => 'form.stock',
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => 1,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.stock_required',
                    ]),
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'form.category',
                'class' => ProductCategory::class,
                'choice_label' => function (ProductCategory $category) {
                    $prefix = '';
                    $current = $category;
                    
                    // Build path for nested categories
                    while ($current->getParent() !== null) {
                        $prefix .= '— ';
                        $current = $current->getParent();
                    }
                    
                    return $prefix . $category->getName();
                },
                'required' => false,
                'placeholder' => 'form.select_category',
                'group_by' => function (ProductCategory $category) {
                    if ($category->getParent() === null) {
                        return 'form.root_categories';
                    }
                    
                    $parent = $category->getParent();
                    while ($parent->getParent() !== null) {
                        $parent = $parent->getParent();
                    }
                    
                    return $parent->getName();
                },
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'form.image_file',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'form.invalid_image_format',
                    ])
                ],
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'form.image_files',
                'required' => false,
                'mapped' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '2M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                            ],
                            'mimeTypesMessage' => 'form.invalid_images_format',
                        ])
                    ])
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'form.is_active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
