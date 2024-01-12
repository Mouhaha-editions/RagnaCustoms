<?php

namespace App\Form;

use App\Entity\SongCategory;
use App\Repository\SongCategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;

#[AsEntityAutocompleteField]
class SongCategoryAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => SongCategory::class,
             'multiple' => true,
            "label" => "Genre",
            'placeholder' => 'Select a category, or more ..',
            'required' => true,

            'query_builder' => function(SongCategoryRepository $songCategoryRepository) {
                return $songCategoryRepository->createQueryBuilder('songCategory')->orderBy('songCategory.label','ASC');
            },
            //'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
