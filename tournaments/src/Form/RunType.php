<?php

namespace App\Form;

use App\Entity\Run;
use App\Entity\RunSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RunType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('runSettings', CollectionType::class, [
                'entry_type' => RunSettingType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'required' => false,
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'attr' => ['placeholder' => "commentaire"]
            ])
            ->add('FinDeRun', SubmitType::class, [
                'attr' => [
                    'id' => "endOfRun",
                    'class' => 'btn btn-xs bg-black float-right'
                ]
            ])
            ->add('Enregistrer', SubmitType::class, [
                'attr' => [
                    'id' => "nextChallenger",
                    'class' => 'btn btn-xs bg-success float-left'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Run::class,
        ]);
    }
}
