<?php

namespace App\Controller\Admin;

use App\Entity\SongCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SongCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SongCategory::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
