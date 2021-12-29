<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongFeedback;
use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin")
     */
    public function index(): Response
    {
        // redirect to some CRUD controller
        $routeBuilder = $this->get(AdminUrlGenerator::class);

        return $this->redirect($routeBuilder->setController(SongFeedbackCrudController::class)->generateUrl());

        // you can also redirect to different pages depending on the current user
//        if ('jane' === $this->getUser()->getUsername()) {
//            return $this->redirect('...');
//        }

        // you can also render some template to display a proper Dashboard
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        return $this->render('@EasyAdmin/page/content.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('RagnaCustoms');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('Feedback', 'fa fa-list', SongFeedback::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Song', 'fa fa-music', Song::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', Utilisateur::class)->setPermission('ROLE_ADMIN');
        yield MenuItem::linkToCrud('Seasons', 'fa fa-tree', Season::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('Difficulties', 'fa fa-star', SongDifficulty::class)->setPermission('ROLE_ADMIN');
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
