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

class EvaluatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Song $entity */
        $entity = $builder->getData();
        $builder
            ->add("zipFile", FileType::class, [
                "mapped" => false,
                "help" => "Upload a .zip file (max 20mb) containing all the files for the map."
            ])

            ->add('check', SubmitType::class, ['attr' => ['class' => 'btn btn-success']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
