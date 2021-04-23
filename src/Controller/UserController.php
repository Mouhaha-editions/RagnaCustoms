<?php

namespace App\Controller;

use App\Form\UtilisateurType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index(TranslatorInterface $translator): Response
    {
        if(!$this->isGranted('ROLE_USER')){
            $this->addFlash('danger',$translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        $em = $this->getDoctrine()->getManager();
        if($this->getUser()->getApiKey() == null){
            $this->getUser()->setApiKey(md5(date('d/m/Y H:i:s').$this->getUser()->getUsername()));
        }
        $em->flush();
//        $form = $this->createForm(UtilisateurType::class, $this->getUser());

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
//            'form'=>$form->createView()
        ]);
    }


}
