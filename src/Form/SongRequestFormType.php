<?php

namespace App\Form;

use App\Entity\SongRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SongRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ["attr" => ["class" => "form-control form-control-sm"]])
            ->add('author', null, ["attr" => ["class" => "form-control form-control-sm"]])
            ->add('link', TextType::class, ["attr" => ["class" => "form-control form-control-sm","placeholder"=>"Youtube link please",]])
            ->add('askThisSong', SubmitType::class, ['attr' => ["class" => "btn btn-info btn-sm mt-1"]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SongRequest::class,
        ]);
    }
}
