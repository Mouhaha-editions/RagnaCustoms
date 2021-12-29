<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // ...
            // (the same permission is granted to the action on all pages)
            ->setPermission(Action::EDIT, 'ROLE_MODERATOR')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ;
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
