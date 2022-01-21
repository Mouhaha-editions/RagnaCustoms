<?php

namespace App\Controller\Admin;

use App\Entity\Song;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
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
        $crud->setDefaultSort(['id'=>"DESC"]);
        return $crud;
    }

//    public function configureFields(string $pageName): iterable
//    {
//        return [
////            IdField::new('id'),
//            TextField::new('name'),
////            TextField::new('user'),
//            BooleanField::new('isModerated'),
//            TextEditorField::new('description'),
//        ];
//    }

}
