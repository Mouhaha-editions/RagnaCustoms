<?php

namespace App\Controller\Admin;

use App\Entity\Song;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
            ->setSearchFields(['name','users.mapper_name', 'users.username'])
            ->setDefaultSort(['id'=>"DESC"]);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('name'),
            ArrayField::new('mappers', 'mapper'),
            BooleanField::new('isModerated'),
            ChoiceField::new('bestPlatform')->setChoices(['vr'=>'0','flat'=>'1']),
            BooleanField::new('isWip'),
            TextEditorField::new('description'),
            DateTimeField::new('lastDateUpload'),
            DateTimeField::new('programmationDate'),
        ];
    }

}
