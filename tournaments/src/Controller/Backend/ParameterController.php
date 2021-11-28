<?php

namespace App\Controller\Backend;

use App\Entity\Parameter;
use App\Form\ParameterNewType;
use App\Form\ParameterType;
use App\Repository\ParameterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/parameters")
 */
class ParameterController extends AbstractController
{
    /**
     * @Route("/", name="parameter_index", methods={"GET"})
     */
    public function index(ParameterRepository $agnTemporaryParameterRepository): Response
    {
        return $this->render('backend/parameter/index.html.twig', [
            'agn_temporary_parameters' => $agnTemporaryParameterRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="parameter_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $agnTemporaryParameter = new Parameter();
        $form = $this->createForm(ParameterNewType::class, $agnTemporaryParameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($agnTemporaryParameter);
            $entityManager->flush();

            return $this->redirectToRoute('parameter_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/parameter/new.html.twig', [
            'agn_temporary_parameter' => $agnTemporaryParameter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="parameter_show", methods={"GET"})
     */
    public function show(Parameter $agnTemporaryParameter): Response
    {
        return $this->render('backend/parameter/show.html.twig', [
            'agn_temporary_parameter' => $agnTemporaryParameter,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="parameter_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Parameter $agnTemporaryParameter): Response
    {
        $form = $this->createForm(ParameterType::class, $agnTemporaryParameter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectRoot = $this->getParameter('kernel.project_dir');
            if ($agnTemporaryParameter->getFieldType() == "file") {
                $file = $form['valueFile']->getData();
                $file->move($projectRoot . "/public/files/", $file->getClientOriginalName());
                $agnTemporaryParameter->setValueFile($file->getClientOriginalName());

            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('parameter_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backend/parameter/edit.html.twig', [
            'agn_temporary_parameter' => $agnTemporaryParameter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="parameter_delete", methods={"POST"})
     */
    public function delete(Request $request, Parameter $agnTemporaryParameter): Response
    {
        if ($this->isCsrfTokenValid('delete' . $agnTemporaryParameter->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($agnTemporaryParameter);
            $entityManager->flush();
        }

        return $this->redirectToRoute('parameter_index', [], Response::HTTP_SEE_OTHER);
    }
}
