<?php

namespace App\Form;

use App\Entity\Song;
use App\Entity\SongCategory;
use App\Entity\SongRequest;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function React\Partial\placeholder;

class SongType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Song $entity */
        $entity = $builder->getData();
        $builder
            ->add("zipFile", FileType::class, [
                "mapped" => false,
                "required" => $entity->getId() == null,
                "help" => "Upload a .zip file (max 20mb) containing all the files for the map."
            ])
            ->add('description', null, [
                'help' => "you can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html' => true
            ])
            ->add('youtubeLink', TextType::class, [
                'label' => "Youtube Link",
                "attr" => ["placeholder " => "https://youtu..."],
                'required' => false
            ])
            ->add('songCategory', EntityType::class, [
                "class" => SongCategory::class,
                "label" => "Category",
                "choice_label" => "label",
                "query_builder" => function (EntityRepository $er) {
                    return $er->createQueryBuilder('sc')->where("sc.isOnlyForAdmin != 1")->orderBy('sc.label');
                },
                "multiple" => false,
                'required' => true
            ])
            ->add('approximativeDuration', HiddenType::class, [
                "label" => "Duration (in sec) ",
                "help" => "leave empty on first upload",
                "attr" => [
                    "placeholder " => "leave empty on first upload",
                ]
            ])
            ->add('song_request', EntityType::class, [
                'mapped' => false,
                'class' => SongRequest::class,
                'required' => false,
                'placeholder' => "Not a requested song",
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
            ->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-success']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Song::class,
        ]);
    }
}
