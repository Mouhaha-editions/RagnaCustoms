<?php


namespace App\Controller\Frontend;


use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
/**
 * @Route("/presentation")
 */
class PresentationPageController extends AbstractController
{
    /**
     * @Route("/",name="presentation_page")
     */
    public function index(UserRepository $userRepository)
    {
        $entities = $userRepository->createQueryBuilder("u")
     ->where('u.twitchID IS NOT NULL')
     ->orderBy("u.twitchID", 'ASC')->getQuery()->getResult();

        return $this->render('frontend/presentation/index.html.twig',[
            'entities'=>$entities
        ]);
    }
}