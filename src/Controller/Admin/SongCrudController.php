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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
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
            FormField::addPanel('Part 1')->setCssClass('col-6'),
            IdField::new('id')->hideOnForm()->setColumns('col-12'),
            TextField::new('name')->setColumns('col-6'),
            TextField::new('authorName')->setColumns('col-6'),
            AssociationField::new('mappers')->setColumns('col-12')->formatValue(function ($value,Song $entity) {
                return implode(', ',$entity->getMappers()->toArray());
            }),
            TextEditorField::new('description')->setColumns('col-12'),
            TextField::new('youtubeLink')->setColumns('col-12')->hideOnIndex(),
            FormField::addPanel('Part 2')->setCssClass('col-6'),
            AssociationField::new('categoryTags')->setColumns('col-12')->hideOnIndex(),
            NumberField::new('beatsPerMinute')->setColumns('col-4')->hideOnIndex(),
            NumberField::new('approximativeDuration')->setColumns('col-4')->hideOnIndex(),
            TextField::new('environmentName')->setColumns('col-4')->hideOnIndex(),
            BooleanField::new('isConverted')->setColumns('col-4'),
            BooleanField::new('isWip')->setColumns('col-4'),
            BooleanField::new('isModerated')->setColumns('col-4'),
            ChoiceField::new('bestPlatform')
                ->setChoices(['VR'=>'0','VOT'=>'1'])
                ->allowMultipleChoices()
                ->renderExpanded()
                ->setColumns('col-4'),
            DateTimeField::new('lastDateUpload')->setColumns('col-4'),
            DateTimeField::new('programmationDate')->setColumns('col-4'),
            TextField::new('slug')->setHelp('verifier que le nouveau slug n\'existe pas déjà')->setColumns('col-12'),
        ];
    }

}
