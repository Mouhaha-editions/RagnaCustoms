<?php

namespace App\Form;

use App\Entity\ChallengeDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChallengeDateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => "datetime",
                "empty_data" => '',
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => "datetime",
                "empty_data" => '',

            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ChallengeDate::class,
        ]);
    }
}
