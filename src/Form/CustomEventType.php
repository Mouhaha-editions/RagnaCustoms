<?php

namespace App\Form;

use App\Entity\CustomEvent;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('description')
            ->add('type')
            ->add('banner')
            ->add('maxChallenger')
            ->add('openningDateRegistration')
            ->add('closingDateRegistration')
            ->add('rules')
            ->add('enabled')
            ->add('edition')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('user', EntityType::class, [
                'class' => Utilisateur::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomEvent::class,
        ]);
    }
}
