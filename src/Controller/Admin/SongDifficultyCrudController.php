<?php

namespace App\Controller\Admin;

use App\Entity\SongDifficulty;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class SongDifficultyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SongDifficulty::class;
    }

    public function updateEntity(EntityManagerInterface $em, $entity): void
    {
        /** SongDifficulty $entity */
        $em->flush();
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('song'),
            AssociationField::new('difficultyRank'),
            BooleanField::new('ranked'),
        ];
    }

}
