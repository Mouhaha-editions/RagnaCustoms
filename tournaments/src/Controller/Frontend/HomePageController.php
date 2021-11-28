<?php


namespace App\Controller\Frontend;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/",name="home")
     */
    public function index()
    {
        return $this->render('frontend/home_page/index.html.twig');
    }

    /**
     * @Route("/locale/{locale}", name="change_locale")
     */
    public function changeLocale(Request $request, string $locale,SessionInterface $session)
    {
        $session->set('_locale', $locale);
        if($request->headers->get('referer') == null){
            return $this->redirectToRoute('home');
        }
        return $this->redirect($request->headers->get('referer') );
    }
}