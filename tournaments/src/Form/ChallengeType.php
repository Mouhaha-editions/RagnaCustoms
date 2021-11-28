<?php

namespace App\Form;

use App\Entity\Challenge;
use App\Entity\Rule;
use App\Entity\Season;
use App\Repository\RuleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'label' => 'challenge.label.title',
                'attr' => [
                    'placeholder' => 'challenge.placeholder.title'
                ]
            ])
            ->add('description', null, ['label' => 'challenge.label.description'])
            ->add('type', ChoiceType::class, [
                "choices" => Challenge::TypesChoices,
                'label' => 'challenge.label.type'
            ])
            ->add('season', EntityType::class, [
                'label' => 'challenge.label.season',
                'class' => Season::class,
                'choice_label' => 'label'
            ])
            ->add('theFile',
                FileType::class, [
                    'label' => 'challenge.label.theFile',
                    // unmapped means that this field is not associated to any entity property
                    'mapped' => false,
                    // make it optional so you don't have to re-upload the PDF file
                    // every time you edit the Product details
                    'required' => false,
                    // unmapped fields can't define their validation using annotations
                    // in the associated entity, so you can use the PHP constraint classes
                    'constraints' => [
                        new File([
                            'maxSize' => '10240k',
                            'mimeTypesMessage' => 'max 10Mo',
                        ])
                    ],
                ])
            ->add('banner',
                FileType::class, [
                    'label' => 'challenge.label.banner',
                    // unmapped means that this field is not associated to any entity property
                    'mapped' => false,
                    // make it optional so you don't have to re-upload the PDF file
                    // every time you edit the Product details
                    'required' => false,
                    // unmapped fields can't define their validation using annotations
                    // in the associated entity, so you can use the PHP constraint classes
                    'constraints' => [
                        new File([
                            'maxSize' => '1024k',
                            'mimeTypes' => [
                                "image/gif",
                                "image/jpeg",
                                "image/png",
                            ],
                            'mimeTypesMessage' => 'Please upload a valid image, max 1Mo',
                        ])
                    ],
                ])
            ->add('display', null, ['label' => 'Afficher en front'])
            ->add('maxChallenger', null, ['label' => 'challenge.label.maxChallenger'])
            ->add('registrationOpening', DateType::class, [
                'format' => DateType::HTML5_FORMAT,
                'widget' => 'single_text',
                'label' => 'challenge.label.registrationOpening'
            ])
            ->add('registrationClosing', DateType::class, [
                'format' => DateType::HTML5_FORMAT,
                'widget' => 'single_text',
                'label' => 'challenge.label.registrationClosing'
            ])
            ->add('rules', EntityType::class, [
                'class' => Rule::class,
                'multiple' => true,
                'choice_label' => function (Rule $rule) {
                    return "(" . $rule->getTypeStr() . ") " . $rule->getLabel();
                },
                "expanded" => true,
                "query_builder" => function (RuleRepository $repository) {
                    return $repository->createQueryBuilder('r')->orderBy('r.type', 'ASC');
                }

            ])
            ->add('challengeDates', CollectionType::class, [
                'entry_type' => ChallengeDateFormType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false
            ])
            ->add('malusPerRun', null, [
                'label' => "Malus par run (en %age)"
            ])
            ->add('malusMax', null, [
                'label' => "Malus maximum"
            ])
            ->add('displayRulesAndRatiosBeforeStart', null, [
                'label' => "Afficher les barèmes avant le début"
            ])
            ->add('displayTotalInMod', null, [
                'label' => "Afficher le total des points dans le mod"
            ])
            ->add('challengePrizes', CollectionType::class, [
                'entry_type' => ChallengePrizeType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Challenge::class,
            'csrf_protection' => false,
        ]);
    }
}
