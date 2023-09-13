<?php

namespace App\Controller\Admin;

use App\Entity\VoteCounter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use phpDocumentor\Reflection\Types\Boolean;

class VoteCounterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VoteCounter::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions

            // ...
            // (the same permission is granted to the action on all pages)
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_MODERATOR')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_ADMIN')
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN');
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setSearchFields(['song.name', 'song.mappers', 'user.username'])
            ->setPaginatorPageSize(60)
            ->setDefaultSort(['updatedAt'=>"DESC"]);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt'),
            TextField::new('user'),
            ArrayField::new('song.mappers','Mapper'),
            TextField::new('song.name', 'Song'),
            BooleanField::new('votesIndc'),
        ];
    }

}
