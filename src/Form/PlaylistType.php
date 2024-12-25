<?php

namespace App\Form;

use App\Entity\Playlist;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaylistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', null, [
                'label'=>'Title',"attr"=>["class"=>"form-control"]])
            ->add('isPublic', null, ["attr"=>["class"=>""]])
            ->add('isFeatured', null, ["attr"=>["class"=>""]])
            ->add('description', null, ["attr"=>["class"=>"form-control"]])
            ->add('save', SubmitType::class, ["attr"=>["class"=>"btn btn-primary btn-block mt-1 mb-1"]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Playlist::class,
        ]);
    }
}
