<?php

namespace App\Controller\Admin;

use App\Entity\Song;
use App\Entity\SongCategory;
use App\Entity\SongDifficulty;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route(path: '/admin')]
    public function index(): Response
    {
        // redirect to some CRUD controller
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($routeBuilder->setController(VoteCrudController::class)->generateUrl());

    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('RagnaCustoms');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Feedback', 'fa fa-list', Vote::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Song', 'fa fa-music', Song::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('SongCategory', 'fa fa-music', SongCategory::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', Utilisateur::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Difficulties', 'fa fa-star', SongDifficulty::class)->setPermission('ROLE_ADMIN');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
