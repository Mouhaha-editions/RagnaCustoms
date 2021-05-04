<?php

namespace App\Form;

use App\Entity\SongFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SongFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isPublic')
            ->add('isAnonymous')
            ->add('feedback')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('song')
            ->add('songDifficulty')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SongFeedback::class,
        ]);
    }
}
