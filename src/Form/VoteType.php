<?php

namespace App\Form;

use App\Entity\SongDifficulty;
use App\Entity\Vote;
use App\Form\Type\RatingType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Vote $entity */
        $entity = $builder->getData();

        $builder
            ->add("funFactor", RatingType::class, [
                'help' => "How much fun did you have playing this map?",
                "label" => "Fun Factor",
                'required'=>true,
            ])
            ->add("rhythm", RatingType::class, [
                'help' => "Are the notes well placed on the beat?",
                "label" => "Rhythm",
                'required'=>true,

            ])
//->add("flow", RatingType::class)
            ->add("patternQuality", RatingType::class, [
                'help' => "Does the notes patterns fits well with the song. Arent they to much repetitive, or any other feeling related to the patterns?",
                "label" => "Pattern quality",
                'required'=>true,

            ])
            ->add("readability", RatingType::class, [
                'help' => "Are the notes patterns/placement/speed easy enough to sight read without having your brain crash?",
                "label" => "Readability",
                'required'=>true,

            ])
//->add("levelQuality", RatingType::class)
//            ->add('songDifficulty', EntityType::class, [
//                'class' => SongDifficulty::class,
//                'label' => "Level to feedback",
//                'query_builder' => function (EntityRepository $er) use ($entity) {
//                    return $er->createQueryBuilder('sd')
//                        ->leftJoin('sd.song','song')
//                        ->where("song.newGuid = :hash")
//                        ->setParameter("hash", $entity->getHash());
//                },
//                "placeholder"=>"Global",
//                "required"=>false,
//                "mapped"=>false,
//                "attr" => [
//                    "class" => "form-control"
//                ]
//            ])

            ->add('feedback', TextareaType::class, [
                "label" => "In any case feedback is welcome, but if your notation is low it helps the mapper to know what to improve in his mapping, thanks a lot.",
                "required" => false,
                "attr" => [
                    "class" => "form-control"
                ]
            ])->add('isPublic', CheckboxType::class, [
                "label" => "make feedback public",
                "required" => false,
                "attr" => [
//                    "class"=>"form-check"
                ]
            ])
            ->add('isAnonymous', CheckboxType::class, [
                "label" => "send it anonymously",
                "required" => false,
                "attr" => [
//                    "class"=>"form-check"
                ]
            ])
            ->add('SaveReview', SubmitType::class, [
                "label" => "Save review!",
                "attr" => [
                    "class" => "btn btn-sm btn-success"
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vote::class,
        ]);
    }
}
