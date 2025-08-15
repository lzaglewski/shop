<?php

declare(strict_types=1);

namespace App\Application\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SmtpSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('smtp_host', TextType::class, [
                'label' => 'Host SMTP',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Host SMTP jest wymagany'
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'np. ssl0.ovh.net',
                    'class' => 'form-control'
                ]
            ])
            ->add('smtp_port', IntegerType::class, [
                'label' => 'Port SMTP',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Port SMTP jest wymagany'
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 65535,
                        'notInRangeMessage' => 'Port musi być między {{ min }} a {{ max }}'
                    ])
                ],
                'attr' => [
                    'placeholder' => '465',
                    'class' => 'form-control'
                ]
            ])
            ->add('smtp_username', EmailType::class, [
                'label' => 'Login (email)',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Login jest wymagany'
                    ]),
                    new Assert\Email([
                        'message' => 'Podaj prawidłowy adres email'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'your-email@domain.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('smtp_password', PasswordType::class, [
                'label' => 'Hasło',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Zostaw puste, aby nie zmieniać',
                    'class' => 'form-control'
                ]
            ])
            ->add('smtp_encryption', ChoiceType::class, [
                'label' => 'Szyfrowanie',
                'choices' => [
                    'SSL/TLS' => 'ssl',
                    'STARTTLS' => 'tls',
                    'Brak' => null
                ],
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('mail_from_email', EmailType::class, [
                'label' => 'Email nadawcy',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Email nadawcy jest wymagany'
                    ]),
                    new Assert\Email([
                        'message' => 'Podaj prawidłowy adres email'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('mail_from_name', TextType::class, [
                'label' => 'Nazwa nadawcy',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Nazwa nadawcy jest wymagana'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'Nazwa Sklepu',
                    'class' => 'form-control'
                ]
            ])
            ->add('mail_admin_emails', TextType::class, [
                'label' => 'Adresy administratorów',
                'required' => true,
                'help' => 'Oddziel przecinkami dla wielu adresów',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Przynajmniej jeden adres administratora jest wymagany'
                    ])
                ],
                'attr' => [
                    'placeholder' => 'admin@domain.com, orders@domain.com',
                    'class' => 'form-control'
                ]
            ])
            ->add('mail_notifications_enabled', CheckboxType::class, [
                'label' => 'Włącz powiadomienia email',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz ustawienia',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('test_connection', SubmitType::class, [
                'label' => 'Testuj połączenie',
                'attr' => [
                    'class' => 'btn btn-info',
                    'formnovalidate' => true
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}