<?php

namespace App\Controller\Admin;

use App\Entity\Changelog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ChangelogController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Changelog::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            // ...
            // (the same permission is granted to the action on all pages)
            ->setPermission(Action::EDIT, 'ROLE_MODERATOR')
            ->setPermission(Action::DELETE, 'ROLE_MODERATOR')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_ADMIN')
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_MODERATOR');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id')->hideOnForm()->hideOnIndex(),
            DateTimeField::new('createdAt'),
            TextareaField::new('description'),
            BooleanField::new('isWip'),
            ArrayField::new('readBy'),
        ];
    }

}
