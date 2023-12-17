<?php

namespace App\Form;

use App\Entity\Country;
use App\Entity\Utilisateur;
use League\OAuth2\Client\Grant\Password;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                "attr" => ["class" => "form-control form-control-sm"],
            ])
            ->add('currentPassword', PasswordType::class, ['mapped' => false, "attr" => ["class" => "form-control form-control-sm"],'required'=>false])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,'required'=>false,

                "attr" => ["class" => "form-control form-control-sm"],
                'first_options' => [
                    "attr" => ["class" => "form-control form-control-sm"],
                    'constraints' => [
                        new Length([
                            'min' => 6,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                    'label' => 'New password',
                ],
                'second_options' => [
                    "attr" => ["class" => "form-control form-control-sm"],
                    'label' => 'Repeat Password',
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
            ->add('email', null, [
                "attr" => ["class" => "form-control form-control-sm"],
            ])
            ->add('usernameColor', ColorType::class, [
                'required' => false,
            ])
            ->add('country', EntityType::class, [
                "class" => Country::class,
                'multiple' => false,
                "label" => "Country",
                'placeholder' => '-- not defined --',
                'required' => false,
                "attr" => ["class" => "form-control form-control-sm mb-2"],

            ])
            ->add('isMapper', null, [
                "required" => false,
                "label" => "I'm a mapper",
            ])
//            ->add('isPublic', null,[
//                "required"=>false,
//                "label"=>"Make my profile public",
//                "attr"=>["class"=>""]
//            ])
            ->add('enableEmailNotification', null, [
                "required" => false,
                "label" => "Enable email notifications",
                "attr" => ["class" => ""],
            ])
            ->add('mapperName', null, [
                "required" => false,
                "label" => "Mapper name",
                "attr" => ["class" => "form-control form-control-sm"],
            ])
            ->add('mapperDiscord', null, [
                "required" => false,
                "label" => "Mapper Discord ID",
                "attr" => ["class" => "form-control form-control-sm"],
            ])
            ->add('mapperDescription', TextareaType::class, [
                "required" => false,
                "label" => "Mapper description",
                'help' => "You can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html' => true,
                "attr" => ["class" => "form-control form-control-sm"],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
