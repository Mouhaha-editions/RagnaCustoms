<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user")
     */
    public function index(Request $request, TranslatorInterface $translator, UtilisateurRepository $utilisateurRepository): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', $translator->trans("You need an account to access this page."));
            return $this->redirectToRoute('home');
        }
        $em = $this->getDoctrine()->getManager();
        if ($this->getUser()->getApiKey() == null) {
            $this->getUser()->setApiKey(md5(date('d/m/Y H:i:s') . $this->getUser()->getUsername()));
        }
        $em->flush();
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email_user = $utilisateurRepository->findOneBy(['email' => $user->getEmail()]);
            if ($email_user != null && $user->getId() !== $email_user->getId()) {
                $form->addError(new FormError("This email is already used."));
            } else {
                $email_user = $utilisateurRepository->findOneBy(['mapper_name' => $user->getMapperName()]);
                if ($email_user != null && $user->getId() !== $email_user->getId()) {
                    $form->addError(new FormError("This mapper name is already used."));
                } else {
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }

        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
            'form' => $form->createView()
        ]);
    }


}
