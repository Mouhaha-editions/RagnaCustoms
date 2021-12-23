<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Fields\HashField;
use App\Entity\SongFeedback;
use App\Service\SongService;
use Container29oBecg\getSongService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
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

    public function configureActions(Actions $actions): Actions
    {
          return $actions
            // ...
            // (the same permission is granted to the action on all pages)
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            // you can set permissions for built-in actions in the same way
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ;
    }
    public function configureCrud(Crud $crud): Crud
    {
        $crud->setDefaultSort(['id'=>"DESC"]);
        return $crud;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('user'),
            TextField::new('feedback'),
            TextField::new('song'),
            BooleanField::new('isModerated'),
//            BooleanField::new('isPublic'),
//            BooleanField::new('isAnonymous'),
        ];
    }

}
