<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', null, ['label' => 'user.label.firstname','attr' => ['placeholder' => 'user.placeholder.firstname']])
            ->add('lastname', null, [
                'label' => 'user.label.lastname',
                'attr' => [
//                    'placeholder' => 'user.placeholder.lastname'
                ]
            ])
            ->add('username', null, [
                'label' => 'user.label.username',
                'attr' => [
//                    'placeholder' => 'user.placeholder.username'
                ]
            ])
            ->add('email', null, [
                'label' => 'user.label.email',
                'attr' => [
//                    'placeholder' => 'user.placeholder.email'
                ]
            ])
            ->add('twitchID', null, [
                'label' => 'user.label.twitchID',
                'required' => false,
                'attr' => [
//                    'placeholder' => 'user.placeholder.twitchID'
                ]
            ])
            ->add('discordID', null, [
                'label' => 'user.label.discordID',
                'required' => true,
                'attr' => [
//                    'placeholder' => 'user.placeholder.discordID'
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'user.label.agreeTerms',
                'translation_domain' => 'messages',

                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
//                    new Regex([
//                        'pattern' => '#^(?=.*[A-Z].*[A-Z])(?=.*[!@#$&*])(?=.*[0-9].*[0-9])(?=.*[a-z].*[a-z].*[a-z]).{8}$#',
//                        'message' => 'Votre mot de passe n\'est pas suffismeent fort, vous devez mettre au moins 8 caractère dont des majuscules '
//                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
