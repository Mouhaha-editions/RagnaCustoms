<?php

namespace App\Form;

use App\Entity\Playlist;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddPlaylistFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur $data */
        $data = $builder->getData();
        $builder
            ->add('playlist', EntityType::class, [
                "class" => Playlist::class,
                "label" => "Select or create a playlist",
                "choice_label" => "label",
                "multiple"=>false,
                "query_builder" => function (EntityRepository $er) use ($data) {
                    return $er->createQueryBuilder("p")
                        ->where('p.user = :user')
                        ->setParameter('user', $data);
                },
                "required" => false,
                "mapped"=>false,
                "placeholder" => "Create new playlist"
            ])
            ->add('newPlaylist', null, [
                "label" => "Name of the new playlist",
                "required"=>false,
                "help"=>"Be careful, by default playlists are public.",
                "mapped"=>false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
