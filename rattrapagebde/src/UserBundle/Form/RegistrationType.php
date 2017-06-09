<?php
// src/UserBundle/Form/RegistrationType.php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('username')
                ->remove('email')
                ->remove('plainPassword')
                ->add('nom', TextType::class, array('label' => 'Nom'))
                ->add('prenom', TextType::class, array('label' => 'Prénom'))
                ->add('password', PasswordType::class, array('label' => 'Mot de passe'))
                ->add('plainPassword', PasswordType::class, array('label' => 'Vérification'))
                ->add('email', EmailType::class, array('label' => 'Adresse mail'))
                ->add('avatar', FileType::class, array('label' => 'Avatar', 'required' => true))
                ->add('roles', 'collection', array(
                        'label' => 't',
                        'type' => 'choice',
                        'options' => array(
                            'choices' => array(
                                'ROLE_BDE' => 'BDE',
                                'ROLE_ETUDIANT' => 'Etudiant',
                            )
                        )
                    )
                )
                ->add('promotion', ChoiceType::class, array(
                        'label' => 'Promotion', 
                        'choices' => array(
                            '1' => 'A1', 
                            '2' => 'A2', 
                            '3' => 'A3', 
                            '4' => 'A4', 
                            '5' => 'A5'
                        )
                    )
                );
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}