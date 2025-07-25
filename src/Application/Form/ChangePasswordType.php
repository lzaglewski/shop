<?php

declare(strict_types=1);

namespace App\Application\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.current_password_required',
                    ]),
                ],
                'label' => 'form.current_password',
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'form.new_password',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'form.new_password_required',
                        ]),
                        new Length([
                            'min' => 8,
                            'minMessage' => 'form.password_min_length',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'form.repeat_new_password',
                ],
                'invalid_message' => 'form.password_mismatch',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
