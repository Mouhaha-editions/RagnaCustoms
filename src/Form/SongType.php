<?php

namespace App\Form;

use App\Entity\Song;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SongType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', null,[
                'help'=>"you can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html'=>true
            ])
            ->add('youtubeLink',TextType::class,[
                'label'=>"Youtube Link",
                'required'=>false
            ])
            ->add('approximativeDuration',null,[
                "label"=>"Duration (in sec)"
            ])
            ->add('wip', null,[
                'label'=> "Work in progress"
            ]) ->add('converted', null,[
                'label'=> "is a converted map ?"
            ])
            ->add('save',SubmitType::class,['attr'=>['class'=>'btn btn-success']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Song::class,
        ]);
    }
}
