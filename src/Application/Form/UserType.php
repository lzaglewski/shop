<?php

declare(strict_types=1);

namespace App\Application\Form;

use App\Domain\User\Model\User;
use App\Domain\User\Model\UserRole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
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
            ->add('role', EnumType::class, [
                'label' => 'form.role',
                'class' => UserRole::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.role_required',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'form.is_active',
                'required' => false,
            ]);

        // Add password fields only for new users or when explicitly requested
        if ($options['require_password']) {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'form.password',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'form.password_required',
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'form.password_min_length',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'form.repeat_password',
                ],
                'invalid_message' => 'form.password_mismatch',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
