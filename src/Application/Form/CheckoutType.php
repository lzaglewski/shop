<?php

declare(strict_types=1);

namespace App\Application\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'checkout.email_address',
                'constraints' => [
                    new NotBlank([
                        'message' => 'checkout.email_required',
                    ]),
                    new Email([
                        'message' => 'checkout.email_invalid',
                    ]),
                ],
            ])
            ->add('contactNumber', TextType::class, [
                'label' => 'checkout.contact_number',
                'required' => false,
            ])
            
            // Adres dostawy
            ->add('deliveryStreet', TextType::class, [
                'label' => 'checkout.delivery_street',
                'required' => false,
            ])
            ->add('deliveryPostalCode', TextType::class, [
                'label' => 'checkout.delivery_postal_code',
                'required' => false,
            ])
            ->add('deliveryCity', TextType::class, [
                'label' => 'checkout.delivery_city',
                'required' => false,
            ])
            
            // Czy adres dostawy jest taki sam jak rozliczeniowy
            ->add('sameAsBilling', CheckboxType::class, [
                'label' => 'checkout.same_delivery_address',
                'mapped' => false,
                'required' => false,
                'data' => false,
            ])
            
            // Adres rozliczeniowy
            ->add('billingCompanyName', TextType::class, [
                'label' => 'checkout.billing_company_name',
                'required' => false,
            ])
            ->add('billingStreet', TextType::class, [
                'label' => 'checkout.billing_street',
                'required' => false,
            ])
            ->add('billingPostalCode', TextType::class, [
                'label' => 'checkout.billing_postal_code',
                'required' => false,
            ])
            ->add('billingCity', TextType::class, [
                'label' => 'checkout.billing_city',
                'required' => false,
            ])
            ->add('billingTaxId', TextType::class, [
                'label' => 'checkout.billing_tax_id',
                'required' => false,
            ])
            
            // Notatki do zamówienia
            ->add('notes', TextareaType::class, [
                'label' => 'checkout.order_notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Nie używamy żadnej encji jako data_class
            // Dane będą przetwarzane w kontrolerze
        ]);
    }
}