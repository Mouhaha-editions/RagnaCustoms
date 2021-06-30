<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use App\Entity\SongDifficulty;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SongDifficultyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SongDifficulty::class;
    }

    public function updateEntity(EntityManagerInterface $em, $entity): void
    {
        /** SongDifficulty $entity */

        if ($entity->getSeasons()->count() != 0) {
            foreach ($entity->getSeasons() as $season) {
                $season->addDifficulty($entity);
                $em->persist($season);
            }
        }
        $em->flush();
    }
    
    public function configureFields(string $pageName): iterable
    {
        $qb =
            $this->getDoctrine()->
            getRepository(Season::class)
                ->createQueryBuilder("s");
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('song'),
            AssociationField::new('difficultyRank'),
            AssociationField::new('seasons'),
            BooleanField::new('ranked'),
        ];
    }

}
