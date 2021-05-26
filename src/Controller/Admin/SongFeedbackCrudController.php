<?php

namespace App\Controller\Admin;

use App\Entity\SongFeedback;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SongFeedbackCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SongFeedback::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            ChoiceField::new('user'),
            TextEditorField::new('feedback'),
            BooleanField::new('isModerated'),
            BooleanField::new('isPublic'),
            BooleanField::new('isAnonymous'),
        ];
    }

}
