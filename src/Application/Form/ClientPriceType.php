<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\Pricing\Model\ClientPrice;
use App\Domain\Product\Model\Product;
use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
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
                'label' => 'form.client',
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
                        'message' => 'form.client_required',
                    ]),
                ],
            ])
            ->add('product', EntityType::class, [
                'label' => 'form.product',
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
                        'message' => 'form.product_required',
                    ]),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'form.price',
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.price_required',
                    ]),
                    new Positive([
                        'message' => 'form.price_positive',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'form.active',
                'help' => 'form.client_price_active_help',
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
