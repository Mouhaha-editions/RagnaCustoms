<?php

namespace App\Form;

use App\Entity\ChallengeSetting;
use App\Entity\RunSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RunSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
            $builder
                ->add('value', TextType::class, [
                    'label' => false,
                    'attr' => [
                        'class' => 'form-control-sm',
                        'onClick' => "this.select();"
                    ]
                ]);


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RunSettings::class,
        ]);
    }
}
