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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
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
            FormField::addPanel('Base')->setCssClass('col-6'),
            IdField::new('id')->hideOnForm()->setColumns('col-12'),
            TextField::new('name')->setColumns('col-12'),
            AssociationField::new('mappers')->setColumns('col-12'),
            AssociationField::new('categoryTags')->setColumns('col-12'),
            TextEditorField::new('description')->setColumns('col-12'),
            TextField::new('youtubeLink')->setColumns('col-12'),
            FormField::addPanel('Base')->setCssClass('col-6'),
            BooleanField::new('isConverted')->setColumns('col-12'),
            ChoiceField::new('bestPlatform')
                ->setChoices(['VR'=>'0','VOT'=>'1'])->allowMultipleChoices()->renderExpanded()->setColumns('col-12'),
            BooleanField::new('isWip')->setColumns('col-12'),
            DateTimeField::new('lastDateUpload')->setColumns('col-12'),
            DateTimeField::new('programmationDate')->setColumns('col-12'),
            BooleanField::new('isModerated')->setColumns('col-12'),
        ];
    }

}
