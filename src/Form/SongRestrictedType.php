<?php

namespace App\Form;

use App\Entity\Song;
use App\Entity\SongCategory;
use App\Entity\SongRequest;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use function React\Partial\placeholder;

class SongRestrictedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Song $entity */
        $entity = $builder->getData();
        $builder
            ->add("zipFile", FileType::class, [
                "mapped"      => false,
                "required"    => $entity->getId() == null,
                "help"        => "Upload a .zip file (max 8Mo) containing all the files for the map, Premium member can upload up to 15Mo.",
                "constraints" => [
                    new File([
                        'maxSize'        => '8M',
                        'maxSizeMessage' => 'You can upload up to 8Mo with a non premium account',
                    ])
                ]
            ])
            ->add('description', null, [
                'help'      => "you can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html' => true
            ])
            ->add('youtubeLink', TextType::class, [
                'label'    => "Youtube Link",
                "attr"     => ["placeholder " => "https://youtu..."],
                'required' => false
            ])
            ->add('categoryTags', Select2EntityType::class, [
                "class"                => SongCategory::class,
                'remote_route'         => 'api_song_categories',
                'multiple'             => true,
                "label"                => "Categories",
                'primary_key'          => 'id',
                'text_property'        => 'label',
                'minimum_input_length' => 0,
                'allow_clear'          => true,
                'delay'                => 250,
                'placeholder'          => 'Select a category, or more ..',

                'required' => true
            ])
            ->add('approximativeDuration', HiddenType::class, [
                "label" => "Duration (in sec) ",
                "help"  => "leave empty on first upload",
                "attr"  => [
                    "placeholder " => "leave empty on first upload",
                ]
            ])
            ->add('song_request', EntityType::class, [
                'mapped'        => false,
                'class'         => SongRequest::class,
                'required'      => false,
                'placeholder'   => "Not a requested song",
                'query_builder' => function (EntityRepository $er) use ($entity) {
                    return $er->createQueryBuilder("sr")
                              ->leftJoin("sr.mapperOnIt", 'mapper')
                              ->where('mapper = :mapperid')
                              ->andWhere('sr.state IN (:available)')
                              ->setParameter('mapperid', $entity->getUser())
                              ->setParameter('available', [SongRequest::STATE_IN_PROGRESS]);
                }
            ])
            ->add('wip', null, [
                'label' => "Work in progress"
            ])
            ->add('isExplicit', null, [
                'label' => "Song containing explicit content"
            ])
            ->add('converted', null, [
                'label' => "is a converted map ?"
            ])
            ->add('bestPlatform', ChoiceType::class, [
                'choices'  => [
                    'Vr'   => 0,
                    'Viking On Tour' => 1,
                ],
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'label'    => 'Mapped for',
            ])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-success']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Song::class,
        ]);
    }
}
