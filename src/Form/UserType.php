<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    // label
                    'Membre' => 'ROLE_USER',
                    'Modérateur' => 'ROLE_MODERATEUR',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true, 
                // several "widgets" HTML
                'expanded' => true,

                'label' => 'Rôle(s)',
                'label_attr' => [
                    'class' => 'checkbox-inline',
                ],
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $form = $event->getForm();
                $user = $event->getData();

                if($user->getId() !== null) 
                {
                $form->add('password', PasswordType::class, [
                    'mapped' => false,
                    'attr' => [
                        'placeholder' => 'Laissez vide si inchangé...',
                    ],
                    'help' => 'Minimum 10 caractères contenant un chiffre, un caractère spécial, une minuscule et une majuscule.',
                ]);

                    // 'empty_data' => '',
                    // 'constraints' => [
                    //     new NotBlank(),
                    //     new Regex('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/',
                    //     'Le mot de passe ne respecte pas les conditions'
                    //     )
                //     ],
                // ]);

                }


                else
                {
                $form->add('password', PasswordType::class, [
                'help' => 'Minimum 10 caractères contenant un chiffre, un caractère spécial, une minuscule et une majuscule.',
                 ]);
                
                }


            })
            ->add('username', TextType::class , [
                'label' => 'Nom d\'utilisateur',
                'empty_data' => ''
                // if editing and removing all the data
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
