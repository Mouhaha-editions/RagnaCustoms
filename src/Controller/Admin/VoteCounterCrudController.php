<?php

namespace App\Controller\Admin;

use App\Entity\VoteCounter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setSearchFields(['song.name', 'song.user.username', 'user.username'])
            ->setPaginatorPageSize(60)
            ->setDefaultSort(['updatedAt'=>"DESC"]);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt'),
            TextField::new('user'),
            TextField::new('song.user','Mapper'),
            TextField::new('song.name', 'Song'),
            BooleanField::new('votesIndc'),
        ];
    }

}
