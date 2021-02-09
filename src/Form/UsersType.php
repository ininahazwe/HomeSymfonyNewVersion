<?php

namespace App\Form;

use App\Entity\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'with-border',
                    'placeholder' => 'firstname'
                ]
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'with-border',
                    'placeholder' => 'Lastname'
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'with-border',
                    'placeholder' => 'Email address'
                ]
            ])
            ->add('avatarUpload', FileType::class, [
                'label' => 'Image (JPG or PNG file)',
                'multiple' => false,
                'required' => false,
                'mapped' => false,
            ])
        ;
        if($options['isAdmin'] === true)
        {
            $builder->add('roles', ChoiceType::class, [
                'multiple' => true,
                'choices' => [
                    'student' => 'ROLE_USER',
                    'mentor' => 'ROLE_ADMIN',
                    'webmaster' => 'ROLE_SUPER_ADMIN'
                ]
            ]);
        }
        if($options['isLogin'] === false)
        {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ]);
        }
        else{
            $builder->add('password', PasswordType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'class' => 'with-border',
                    'placeholder' => 'password'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'isAdmin' => false,
            'isLogin' => false
        ]);
    }
}
