<?php

namespace App\Controller\Backend;

use App\Entity\Challenge;
use App\Entity\Participation;
use App\Entity\Run;
use App\Entity\RunSettings;
use App\Entity\User;
use App\Form\ChallengeType;
use App\Form\RunType;
use App\Repository\ChallengeRepository;
use App\Repository\RunRepository;
use App\Repository\UserRepository;
use App\Service\ChallengeService;
use App\Service\RunService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * @Route("/admin/run")
 */
class RunController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="run_admin_current_new", methods={"GET","POST"})
     * @param Request $request
     * @param User $user
     * @param Challenge|null $challenge
     * @param ChallengeRepository $challengeRepository
     * @param RunRepository $runRepository
     * @param RunService $runService
     * @param bool $reset
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function current(Request $request, User $user, ChallengeRepository $challengeRepository, RunRepository $runRepository, RunService $runService, $reset = false): Response
    {

        $challenge = $challengeRepository->find($request->get('challenge'));
        if ($challenge == null) {
            return new JsonResponse([
                'success' => false,
                "message" => "Aucun challenge en cours"
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $run = $runRepository->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.challenge  = :challenge')
            ->andWhere('r.endDate IS NULL')
            ->andWhere('r.training  IS NULL OR r.training = 0 ')
            ->setParameter('challenge', $challenge)
            ->setParameter('user', $user)
            ->orderBy('r.id', 'DESC')
            ->setFirstResult(0)->setMaxResults(1)

            ->getQuery()
            ->getOneOrNullResult();

        /** @var Run $run */
        if ($run == null) {
            $run = new Run();
            $run->setChallenge($challenge);
            $run->setUser($user);
            $user->addRun($run);
            $entityManager->persist($run);
            $run->setStartDate(new \DateTime());
            $countRun = $user->countRun($challenge);
            /** @var Run $lastrun */
            $lastrun = $runRepository->createQueryBuilder('r')
                ->where('r.user = :user')
                ->andWhere('r.challenge  = :challenge')
                ->andWhere('r.training IS NOT NULL')
                ->setParameter('challenge', $challenge)
                ->setParameter('user', $user)
                ->orderBy("r.endDate", "DESC")
                ->getQuery()
                ->setMaxResults(1)
                ->setFirstResult(0)
                ->getOneOrNullResult();

            foreach ($challenge->getChallengeSettings() as $setting) {
                $runSetting = new RunSettings();
                $runSetting->setChallengeSetting($setting);
                $runSetting->setRun($run);
                if ($lastrun != null && $setting->getIsReportedOnTheNextRun()) {
                    foreach ($lastrun->getRunSettings() as $lastSetting) {
                        if ($lastSetting->getChallengeSetting()->getId() == $setting->getId()) {
                            $runSetting->setValue($lastSetting->getValue());
                            break;
                        }
                    }
                } else {
                    $runSetting->setValue($setting->getDefaultValue());
                }
                $run->addRunSetting($runSetting);
                $malus = $challenge->getMalusPerRun() * ($countRun - 1);
                $malus = $malus >= $challenge->getMalusMax() ? $challenge->getMalusMax() : $malus;
                $run->setMalus(1 - ($malus / 100));
                $entityManager->persist($runSetting);
            }
            $entityManager->flush();
        }

        $form = $this->createForm(RunType::class, $run, [
            'attr' => [
                'id' => 'runForm',
                'data-challenger' => $user->getId(),
                'data-challenge' => $challenge->getId(),
            ],
            'action' => $this->generateUrl('run_admin_current_new', ['id' => $user->getId()])
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $run->setLastVisitedAt(new \DateTime());
            $runService->ComputeScore($run);
            $entityManager->flush();
            if ($request->get('button', null) == "run_FinDeRun" && $reset == false) {
                $runService->endOfRun($run);

                $reset = true;
            }
        }

        return new JsonResponse([
            'success' => true,
            'refresh' => $reset,
            'html' => $this->renderView('backend/run/_form.html.twig', [
                'form' => $form->createView(),
                'challenger' => $user,
                'run' => $run,
                'challenge' => $run->getChallenge(),
            ])
        ]);

    }

    /**
     * @Route("/infos/{id}/challenge/{id_challenge}", name="admin_runs_info")
     * @ParamConverter("challenge", options={"mapping": {"id_challenge": "id"}})
     * @ParamConverter("user", options={"mapping": {"id": "id"}})
     * @param Request $request
     * @param User $user
     * @param Challenge $challenge
     * @param ChallengeService $challengeService
     * @param RunRepository $runRepository
     * @return Response
     */
    public function infoRuns(Request $request, User $user, Challenge $challenge, ChallengeService $challengeService, RunRepository $runRepository)
    {
        if ($challenge == null) {
            $challenge = $challengeService->getRunningChallenge();
        }
        $runs = $runRepository->findByUserAndChallenge($user, $challenge);
        return $this->render('backend/run/info.html.twig', [
            'user' => $user,
            'challenge' => $challenge,
            'runs' => $runs
        ]);
    }

    /**
     * @Route("/delete-participation/{id}", name="run_admin_delete_participation", methods={"GET","POST"})
     * @param Request $request
     * @param Participation $participation
     * @return Response
     */
    public function deleteParticipation(Request $request, Participation $participation): Response
    {
        $this->getDoctrine()->getManager()->remove($participation);

        return new JsonResponse([
            'success' => true,
            'replace' => ""
        ]);
    }

    /**
     * @Route("/edit/oneshot/{id}", name="admin_run_edit")
     * @param Request $request
     * @param Run $run
     * @param RunService $runService
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editRun(Request $request, Run $run, RunService $runService)
    {
        $form = $this->createForm(RunType::class, $run);
        $form->remove('FinDeRun');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $runService->ComputeScore($run);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Run modifiée');
            return $this->redirectToRoute('admin_runs_info', [
                'id' => $run->getUser()->getId(),
                'id_challenge' => $run->getChallenge()->getId()
            ]);
        }

        return $this->render('backend/run/edit.html.twig', [
            'form' => $form->createView(),
            'run' => $run,
            'challenger' => $run->getUser(),
            'challenge' => $run->getChallenge()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="run_admin_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Challenge $challenge
     * @param SluggerInterface $slugger
     * @param UserRepository $userRepository
     * @return Response
     */
    public function edit(Request $request, Challenge $challenge, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $originalDates = new ArrayCollection();
        foreach ($challenge->getChallengeDates() as $date) {
            $originalDates->add($date);
        }
        $originalPrizes = new ArrayCollection();
        foreach ($challenge->getChallengePrizes() as $prize) {
            $originalPrizes->add($prize);
        }


        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $banner = $form->get('banner')->getData();
            foreach ($originalDates as $date) {
                if (false === $challenge->getChallengeDates()->contains($date)) {
                    $date->setChallenge(null);
                    $entityManager->persist($date);
                    $entityManager->remove($date);
                }
            }
            foreach ($originalPrizes as $prize) {
                if (false === $challenge->getChallengePrizes()->contains($prize)) {
                    $prize->setChallenge(null);
                    $entityManager->persist($prize);
                    $entityManager->remove($prize);
                }
            }

            foreach ($challenge->getChallengeDates() as $challengeDate) {
                $challengeDate->setChallenge($challenge);
            }

            foreach ($challenge->getChallengePrizes() as $challengePrize) {
                $challengePrize->setChallenge($challenge);
            }
            if ($banner) {
                $originalFilename = pathinfo($banner->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $banner->guessExtension();
                try {
                    $banner->move(
                        $this->getParameter('challenge_banner_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $challenge->setBanner($newFilename);
            }

            $entityManager->persist($challenge);
            $entityManager->flush();

            return $this->redirectToRoute('challenge_admin_index');
        }
        $allPlayers = $userRepository->createQueryBuilder('u')
            ->orderBy('u.username')
            ->getQuery()
            ->getResult();

        $availablePlayer = [];
        foreach ($allPlayers as $user) {
            $result = true;
            foreach ($user->getParticipations() as $participation) {
                if ($participation->getChallenge() === $challenge) {
                    $result = false;
                }
            }
            if ($result) {
                $availablePlayer[] = $user;
            }
        }

        $arbitres = $userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :employee')
            ->setParameter('employee', '%ROLE_ARBITRE%')
            ->orderBy("u.username")
            ->getQuery()->getResult();
        return $this->render('backend/challenge/create_edit.html.twig', [
            'challenge' => $challenge,
            'form' => $form->createView(),
            'arbitres' => $arbitres,
            'availablePlayer' => $availablePlayer,
        ]);
    }

    /**
     * @Route("/{id}", name="run_admin_delete", methods={"GET"})
     * @param Request $request
     * @param Run $run
     * @param RunRepository $runRepository
     * @return Response
     */
    public function delete(Request $request, Run $run, RunRepository $runRepository, RunService $runService): Response
    {

        $user = $run->getUser();
        $challenge = $run->getChallenge();

        $entityManager = $this->getDoctrine()->getManager();
        foreach($run->getRunSettings() AS $setting){
            $entityManager->remove($setting);
        }
        $entityManager->remove($run);
        $entityManager->flush();

        $runs = $runRepository->findBy([
            'user' => $user,
            'training'=>false,
            "challenge" => $challenge
        ], ['id' => "ASC"]);
        $countRun = 1;
        foreach ($runs as $run) {
            $malus = $challenge->getMalusPerRun() * ($countRun - 1);
            $malus = $malus >= $challenge->getMalusMax() ? $challenge->getMalusMax() : $malus;
            $run->setMalus(1 - ($malus / 100));
            $runService->ComputeScore($run);
            $entityManager->flush();
            $countRun++;
        }
        return $this->redirectToRoute('admin_runs_info',[
            'id_challenge'=>$challenge->getId(),
            'id'=>$user->getId(),
        ]);
    }

}
