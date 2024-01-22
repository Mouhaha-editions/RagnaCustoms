<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\UtilisateurRepository;
use App\Security\EmailVerifier;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route(path: "/verify/resend", name: "app_verify_resend_email")]
    public function resendVerifyEmail(Request $request, UtilisateurRepository $utilisateurRepository)
    {
        $user = null;
        if ($request->isMethod('POST') && $request->request->has('username')) {
            $username = $request->request->get('username');

            if (str_contains($username, '@')) {
                $user = $utilisateurRepository->findOneBy(['email' => $username]);
            }

            if (!$user) {
                $user = $utilisateurRepository->findOneBy(['username' => $username]);
            }

            if ($user) {
                if (!$user->isVerified()) {
                    $this->sendEmailVerification($user);
                }
            }
        }

        return $this->render('registration/resend_verify_email.html.twig', [
            'done' => null !== $user
        ]);
    }

    private function sendEmailVerification(Utilisateur $user)
    {
        // generate a signed url and email it to the user
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('contact@ragnacustoms.com', 'RagnaCustoms Bot'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setMapperName($user->getUsername());
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendEmailVerification($user);
            // do anything else you need here, like send an email
            $this->addFlash('success', "Your account has been created.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $user = $utilisateurRepository->findOneBy(['id' => $request->query->getInt('id')]);
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            if(!$user){
                throw new \Exception('Can\'t found account');
            }

            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());
            return $this->redirectToRoute('app_register');
        } catch(\Exception $e){
            $this->addFlash('verify_email_error', $e->getMessage());
            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_login');
    }
}
