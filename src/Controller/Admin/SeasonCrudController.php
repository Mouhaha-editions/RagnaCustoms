<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SeasonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Season::class;
    }

    public function updateEntity(EntityManagerInterface $em, $entity): void
    {
        /** SongDifficulty $entity */

        if ($entity->getDifficulties()->count() != 0) {
            foreach ($entity->getDifficulties() as $diff) {
                $diff->addSeason($entity);
                $em->persist($diff);
            }
        }
        $em->flush();
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('label'),
            DateField::new('startDate'),
            DateField::new('endDate'),
            AssociationField::new('difficulties'),
        ];
    }

}
