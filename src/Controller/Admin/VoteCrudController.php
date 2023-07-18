<?php

namespace App\Controller\Admin;

use App\Entity\Vote;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\VarDumper\VarDumper;

class VoteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vote::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            // ...
            // (the same permission is granted to the action on all pages)
            ->setPermission(Action::EDIT, 'ROLE_MODERATOR')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setDefaultSort(['id' => "DESC"]);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('createdAt'),
            TextField::new('user'),
            TextField::new('song.mapper','Mapper'),
            TextField::new('song'),
            NumberField::new('funFactor'),
            NumberField::new('rhythm'),
            NumberField::new('patternQuality'),
            NumberField::new('readability'),
            TextField::new('feedback'),
            BooleanField::new('isModerated'),
//            BooleanField::new('isPublic'),
//            BooleanField::new('isAnonymous'),
        ];
    }

}
