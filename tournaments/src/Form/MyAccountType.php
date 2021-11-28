<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MyAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('email')
            ->add('firstname')
            ->add('lastname')
            ->add('discordID')
            ->add('twitchID', null,["required"=>false])
            ->add('bannerlordID',null,["required"=>false])
            ->add('steamID',null,["required"=>false])
            ->add('twitter', UrlType::class,["required"=>false])
            ->add('youtube',UrlType::class,["required"=>false])
            ->add('instagram',UrlType::class,["required"=>false])
            ->add('levelMulti', ChoiceType::class,[
                'choices'=>[
                    "Occasionnel"=>User::NIVEAU_DEBUTANT,
                    "Intermédiaire"=>User::NIVEAU_INTERMEDIAIRE,
                    "Confirmé"=>User::NIVEAU_CONFIRMED,
                ],
                'required'=>false,
                "placeholder"=>"-- Séléctionner --"
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
