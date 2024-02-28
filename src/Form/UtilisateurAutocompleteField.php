<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class UtilisateurAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Utilisateur::class,
            'filter_query' => function (QueryBuilder $qb, string $query, EntityRepository $repository) {
                if (!$query) {
                    return;
                }

                $qb->andWhere('u.mapper_name LIKE :search')
                    ->setParameter('search', $query.'%');
            },
            'query_builder' => function (UtilisateurRepository $utilisateurRepository) {
                // return $utilisateurRepository->createQueryBuilder('utilisateur');
                return $utilisateurRepository
                    ->createQueryBuilder("u")
                    ->distinct()
                    ->leftJoin('u.songsMapped', 's')
                    ->andWhere('s.isDeleted = false')
                    ->andWhere('s.wip = false')
                    ->andWhere('s.moderated = true')
                    ->andWhere('s.active = true')
                    ->orderBy('u.mapper_name', 'ASC');
            },
            'tom_select_options' => [
                'openOnFocus'=>false,
                'placeholder'=>'Enter a mapper’s name (they need to publish at least one map to appear in the list)',
                'hidePlaceholder'=>true,
            ],
            'multiple' => true,
            // 'autocomplete'=>true,
            "label" => 'Mapper(s)',
            'placeholder' => 'Enter a mapper’s name (they need to publish at least one map to appear in the list)',
            'help' => 'Be careful: mappers added here will get the same permissions as you on the map',
            'required' => false,

            //'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
