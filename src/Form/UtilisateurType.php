<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('username', null)
//            ->add('password')
            ->add('email', null,[
                "attr"=>["class"=>"form-control form-control-sm"]
            ])
            ->add('isMapper', null,[
                "required"=>false,
                "label"=>"I'm a mapper"
            ])
            ->add('isPublic', null,[
                "required"=>false,
                "label"=>"Make my profile public",
                "attr"=>["class"=>""]
            ])
            ->add('enableEmailNotification', null,[
                "required"=>false,
                "label"=>"Enable email notifications",
                "attr"=>["class"=>""]
            ])
            ->add('mapperName', null,[
                "required"=>false,
                "label"=>"Mapper name",
                "attr"=>["class"=>"form-control form-control-sm"]
            ])
            ->add('mapperDiscord', null,[
                "required"=>false,
                "label"=>"Mapper Discord ID",
                "attr"=>["class"=>"form-control form-control-sm"]
            ])
            ->add('mapperDescription', TextareaType::class,[
                "required"=>false,
                "label"=>"Mapper description",
                'help'=>"You can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html'=>true,
                "attr"=>["class"=>"form-control form-control-sm"]
            ])
            ->add('Save', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
