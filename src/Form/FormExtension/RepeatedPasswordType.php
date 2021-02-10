<?php

namespace App\Form\FormExtension;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RepeatedPasswordType extends AbstractType
{
    public function getParent(): string
    {
        return RepeatedType::class;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
       $resolver->setDefaults([
           'type'            => PasswordType::class,
           'invalid_message' => "Passwords must be the same",
           'required'        => true,
           'first_options'   => [
               'label' => "Password",
               'label_attr'  => [

                   'title'   => "Pour des raisons de sécurité, votre mot de passe doit contenir au minimum 12 caractères"
               ],
               'attr'          => [
                   'maxlength' => 255,
                   'title'     => "Pour des raisons de sécurité, votre mot de passe doit contenir au minimum 12 caractères"
               ]
           ],
           'second_options' => [
               'label' => "confirm your password",
               'label_attr' => [
                   'title'  => "confirm your password"
               ],
               'attr'          => [
                   'maxlength' => 255,
                   'title'     => "confirm your password"
               ]
           ]
       ]);
    }
}