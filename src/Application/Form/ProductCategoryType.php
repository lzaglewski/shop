<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\Product\Model\ProductCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a category name',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ]);
            
        // Add parent field with different configuration based on whether it's locked
        $parentFieldOptions = [
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
            'placeholder' => 'No parent (root category)',
            'group_by' => function (ProductCategory $category) {
                if ($category->getParent() === null) {
                    return 'Root Categories';
                }
                
                $parent = $category->getParent();
                while ($parent->getParent() !== null) {
                    $parent = $parent->getParent();
                }
                
                return $parent->getName();
            },
        ];
        
        // If parent is locked, make the field disabled
        if ($options['parent_locked']) {
            $parentFieldOptions['disabled'] = true;
            $parentFieldOptions['help'] = 'Parent category cannot be changed';
        }
        
        $builder->add('parent', EntityType::class, $parentFieldOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductCategory::class,
            'parent_locked' => false,
        ]);
        
        $resolver->setAllowedTypes('parent_locked', 'bool');
    }
}
