<?php

namespace App\Form;

use App\Entity\Categories;
use App\Entity\Projects;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('description', TextareaType::class)
            ->add('photoUpload', FileType::class, [
                'required' => false,
                'mapped' =>false,
            ])
            ->add('users', EntityType::class, [
                // looks for choices from this entity
                'class' => Users::class,

                // uses the User.username property as the visible option string
                'choice_label' => 'firstname',
                'expanded' => true,
                'multiple' => true
            ])
            ->add('category', EntityType::class, [
                // looks for choices from this entity
                'class' => Categories::class,

                // uses the User.username property as the visible option string
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Projects::class,
        ]);
    }
}
