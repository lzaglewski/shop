<?php

declare(strict_types=1);

namespace App\Application\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InfoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'info.name_label',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'info.name_required',
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'info.name_min_length',
                        'maxMessage' => 'info.name_max_length',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'info.email_required',
                    ]),
                    new Email([
                        'message' => 'info.email_invalid',
                    ]),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'info.subject',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'info.subject_required',
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'info.subject_min_length',
                        'maxMessage' => 'info.subject_max_length',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'info.message',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'info.message_required',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'info.message_min_length',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'info.send_button',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class needed since we're not binding to an entity
        ]);
    }
}