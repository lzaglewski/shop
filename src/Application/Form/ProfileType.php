<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\User\Model\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.email_required',
                    ]),
                    new Email([
                        'message' => 'form.email_invalid',
                    ]),
                ],
            ])
            ->add('companyName', TextType::class, [
                'label' => 'form.company_name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.company_name_required',
                    ]),
                ],
            ])
            ->add('taxId', TextType::class, [
                'label' => 'form.tax_id',
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.tax_id_required',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
