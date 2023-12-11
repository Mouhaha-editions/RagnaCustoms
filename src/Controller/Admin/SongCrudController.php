<?php

namespace App\Controller\Admin;

use App\Entity\Song;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;


class SongCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Song::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setSearchFields(['name', 'mappers.mapper_name', 'mappers.username'])
            ->setDefaultSort(['id' => "DESC"]);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('mappers'),
            AssociationField::new('categoryTags'),
            TextEditorField::new('description'),
            TextEditorField::new('youtubeLink'),
            DateTimeField::new('lastDateUpload'),
            DateTimeField::new('programmationDate'),
            BooleanField::new('isModerated'),
            BooleanField::new('isConverted'),
            ChoiceField::new('bestPlatform')
                ->setChoices(['VR'=>'0','VOT'=>'1']),
            BooleanField::new('isWip'),
        ];
    }

}
