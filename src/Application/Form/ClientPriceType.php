<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\Model\Pricing\ClientPrice;
use App\Domain\Model\Product\Product;
use App\Domain\Model\User\User;
use App\Domain\Model\User\UserRole;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ClientPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'companyName',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->andWhere('u.isActive = :active')
                        ->setParameter('role', UserRole::CLIENT)
                        ->setParameter('active', true)
                        ->orderBy('u.companyName', 'ASC');
                },
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a client',
                    ]),
                ],
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'name',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('p.name', 'ASC');
                },
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a product',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a price',
                    ]),
                    new Positive([
                        'message' => 'Price must be positive',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
            ])
        ;

        // If client ID is provided in options, set the client field as disabled
        if (isset($options['client_id']) && $options['client_id']) {
            $builder->get('client')->setDisabled(true);
        }
        
        // If product ID is provided in options, set the product field as disabled
        if (isset($options['product_id']) && $options['product_id']) {
            $builder->get('product')->setDisabled(true);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClientPrice::class,
            'client_id' => null,
            'product_id' => null,
        ]);
        
        $resolver->setAllowedTypes('client_id', ['null', 'int']);
        $resolver->setAllowedTypes('product_id', ['null', 'int']);
    }
}
