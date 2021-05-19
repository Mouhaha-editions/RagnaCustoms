<?php

namespace App\Form;

use App\Entity\SongDifficulty;
use App\Entity\SongFeedback;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SongFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var SongFeedback $entity */
        $entity = $builder->getData();
        $builder
            ->add('songDifficulty', EntityType::class, [
                'class' => SongDifficulty::class,
                'label' => "Level to feedback",
                'query_builder' => function (EntityRepository $er) use ($entity) {
                    return $er->createQueryBuilder('sd')
                        ->where("sd.song = :song")
                        ->setParameter("song", $entity->getSong());
                },
                "placeholder"=>"Global",
                "required"=>false,
                "attr" => [
                    "class" => "form-control"
                ]
            ])
            ->add('isPublic', null, [
                "label" => "make it public",
                "required"=>false,

                "attr" => [
                ]
            ])
            ->add('isAnonymous', null, [
                "label" => "send it anonymously",
                "required"=>false,
                "attr" => [
                ]
            ])
            ->add('feedback', TextareaType::class, [
                "label" => "your feedback",
                "required"=>true,
                "attr" => [
                    "class" => "form-control"
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SongFeedback::class,
        ]);
    }
}
