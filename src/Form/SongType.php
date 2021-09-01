<?php

namespace App\Form;

use App\Entity\Song;
use App\Entity\SongCategory;
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
            ->add('songCategory', null, [
                "class" => SongCategory::class,
                "label" => "Category",
                "choice_label" => "label",
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
            ->add('wip', null, [
                'label' => "Work in progress"
            ])->add('converted', null, [
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
