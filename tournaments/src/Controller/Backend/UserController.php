<?php

namespace App\Controller\Backend;

use App\Entity\User;
use App\Form\RegistrationAdminType;
use App\Form\RegistrationFormType;
use App\Helper\XmlResponse;
use App\Repository\UserRepository;
use Pkshetlie\PaginationBundle\Service\Calcul;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_admin_index", methods={"GET"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param Calcul $pagination
     * @return Response
     */
    public function index(Request $request, UserRepository $userRepository, Calcul $pagination): Response
    {
        $qb = $userRepository
            ->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->addOrderBy("u.id", "DESC");
        if ($request->get('search')) {
            $qb->where('u.username LIKE :search')
                ->orWhere("u.email LIKE :search")
                ->orWhere("u.lastname LIKE :search")
                ->orWhere("u.firstname LIKE :search")
                ->setParameter('search', "%" . $request->get('search') . "%");
        }

        $paginator = $pagination->setDefaults(50)->process($qb, $request);
        if ($this->isGranted('ROLE_USER')) {
            $user = $this->getUser();
            $em = $this->getDoctrine()->getManager();
            if ($user->getApiKey() == null) {
                $user->setApiKey(md5($user->getUsername() . $user->getEmail() . date('d_m_y_s')));
                $em->flush();
            }
        }

        return $this->render('backend/user/index.html.twig', [
            'paginator' => $paginator,
        ]);
    }

    /**
     * @Route("/new", name="user_admin_new", methods={"GET","POST"})
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        return $this->edit($request, new User(), $passwordEncoder);
    }


    /**
     * @Route("/{id}/edit", name="user_admin_edit", methods={"GET","POST"})
     * @param Request $request
     * @param User $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function edit(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(RegistrationAdminType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('plainPassword')->getData() != null) {
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_admin_index');
        }

        return $this->render('backend/user/create_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_admin_delete", methods={"GET"})
     * @param User $rule
     * @return Response
     */
    public function delete(User $rule): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($rule);
        $entityManager->flush();
        return $this->redirectToRoute('user_admin_index');
    }

    /**
     * @Route("/gen/file/{id}", name="user_admin_generate_apikey_file")
     *
     */
    public function genFile(User $user)
    {
        $em = $this->getDoctrine()->getManager();

        if ($user->getApiKey() == null) {
            $user->setApiKey(md5($user->getUsername() . $user->getEmail() . date('d_m_y_s')));
            $em->flush();
        }

        $response = new XmlResponse("<configuration>
    <apiKey value='" . $user->getApiKey() . "'/>
</configuration>");
        $response->headers->add([
            "Content-Disposition" => ResponseHeaderBag::DISPOSITION_ATTACHMENT . "; filename=\"Key.xml\"",
        ]);

        return $response;

    }
}
