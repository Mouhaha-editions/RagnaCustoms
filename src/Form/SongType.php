<?php

namespace App\Form;

use App\Entity\Song;
use App\Entity\SongCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class SongType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Song $entity */
        $entity = $builder->getData();
        $builder
            ->add('description', null, [
                'help' => "You can use <a target=\"_blank\" href=\"https://guides.github.com/features/mastering-markdown/\">Markdown</a> in description",
                'help_html' => true
            ])
            ->add('youtubeLink', TextType::class, [
                'label' => "Youtube Link",
                "attr" => ["placeholder " => "https://youtu..."],
                'required' => false
            ])
            ->add('categoryTags', Select2EntityType::class, [
                "class" => SongCategory::class,
                'remote_route' => 'api_song_categories',
                'multiple' => true,
                "label" => "Genre",
                'primary_key' => 'id',
                'text_property' => 'label',
                'minimum_input_length' => 0,
                'allow_clear' => true,
                'delay' => 250,
                'placeholder' => 'Select a category, or more ..',

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
            ])
            ->add('active', null, [
                'label' => "Publish"
            ])
            ->add('isExplicit', null, [
                'label' => "Explicit content"
            ])
            ->add('bestPlatform', ChoiceType::class, [
                'choices' => [
                    'VR' => 0,
                    'Vikings on Tour' => 1,
                ],
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'label' => 'Mapped for',
            ])
            ->add('converted', null, [
                'label' => "Converted map"
            ])
            ->add('save', SubmitType::class, ['attr' => ['class' => 'btn btn-info btn-lg btn-block']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Song::class,
        ]);
    }
}
