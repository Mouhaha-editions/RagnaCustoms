<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setSearchFields(['username','email', 'mapper_name'])
            ->setDefaultSort(['id'=>"DESC"]);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('username'),
            TextField::new('email'),
            CountryField::new('country.twoLetters', 'Pays'),
            TextField::new('mapperName'),
            DateTimeField::new('createdAt'),
            BooleanField::new('verified'),
        ];
    }

}
