<?php

namespace App\Form;

use App\Entity\Users;
use App\Form\FormExtension\RepeatedPasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType
{
    /**
     * Build a form with html attributes and Validator constraints.
     *
     * @param FormBuilderInterface<callable> $builder
     * @param array<mixed> $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => "Firstname",
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('lastname', TextType::class, [
                'label' => "Lastname",
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => "Email",
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('password', RepeatedPasswordType::class)
            ->add('agreeTerms', CheckboxType::class, [
                'label' => "I accept the terms of use of this site",
                'label_attr' => ['class' => 'switch-custom'],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}
