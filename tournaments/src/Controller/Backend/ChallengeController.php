<?php

namespace App\Controller\Backend;

use App\Entity\Challenge;
use App\Entity\ChallengeDate;
use App\Entity\ChallengePrize;
use App\Entity\ChallengeSetting;
use App\Entity\Participation;
use App\Entity\Rule;
use App\Form\ChallengeType;
use App\Repository\ChallengeRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Pkshetlie\PaginationBundle\Service\Calcul;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @Route("/admin/challenge")
 */
class ChallengeController extends AbstractController
{
    /**
     * @Route("/duplicate/{id}", name="challenge_admin_duplicate")
     * @param Request $request
     * @param Challenge $challenge
     * @return Response
     */
    public function duplicate(Challenge $challenge)
    {
        $em = $this->getDoctrine()->getManager();

        $newChallenge = new Challenge();
        $newChallenge->setType($challenge->getType());
        $newChallenge->setUser($challenge->getUser());
        $newChallenge->setMalusPerRun($challenge->getMalusPerRun());
        $newChallenge->setMalusMax($challenge->getMalusMax());
        $newChallenge->setDescription($challenge->getDescription());
        $newChallenge->setSeason($challenge->getSeason());
        $newChallenge->setRegistrationOpening($challenge->getRegistrationOpening());
        $newChallenge->setRegistrationClosing($challenge->getRegistrationClosing());
        $newChallenge->setTitle($challenge->getTitle());
        $newChallenge->setBanner($challenge->getBanner());
        $newChallenge->setDisplay($challenge->getDisplay());
        $newChallenge->setMaxChallenger($challenge->getMaxChallenger());
        $newChallenge->setDisplayRulesAndRatiosBeforeStart($challenge->getDisplayRulesAndRatiosBeforeStart());
        $newChallenge->setDisplayTotalInMod($challenge->getDisplayTotalInMod());
        $em->persist($newChallenge);

        foreach ($challenge->getChallengeSettings() as $setting) {
            $newSetting = new ChallengeSetting();
            $newSetting->setChallenge($newChallenge);
            $newSetting->setRatio($setting->getRatio());
            $newSetting->setAutoValue($setting->getAutoValue());
            $newSetting->setDefaultValue($setting->getDefaultValue());
            $newSetting->setlabel($setting->getLabel());
            $newSetting->setDisplayBestForStats($setting->getDisplayBestForStats());
            $newSetting->setDisplayForStats($setting->getDisplayForStats());
            $newSetting->setInputType($setting->getInputType());
            $newSetting->setPosition($setting->getPosition());
            $newSetting->setIsAffectedByMalus($setting->getIsAffectedByMalus());
            $newSetting->setIsReportedOnTheNextRun($setting->getIsReportedOnTheNextRun());
            $newSetting->setIsStepToVictory($setting->getIsStepToVictory());
            $newSetting->setIsUsedForScore($setting->getIsUsedForScore());
            $newSetting->setSubTotal($setting->getSubTotal());

            $em->persist($newSetting);
//            $em->clear(ChallengeSetting::class);
        }

        foreach ($challenge->getChallengePrizes() as $prize) {
            $newPrize = new ChallengePrize();
            $newPrize->setChallenge($newChallenge);
            $newPrize->setDescription($prize->getDescription());
            $newPrize->setValue($prize->getValue());
            $newPrize->setLabel($prize->getLabel());
            $newChallenge->addChallengePrize($newPrize);
            $em->persist($newPrize);
        }

        foreach ($challenge->getRules() as $rules) {
            $newChallenge->addRule($rules);
            $rules->addChallenge($newChallenge);
        }

        foreach ($challenge->getChallengeDates() as $dates) {
            $newDate = new ChallengeDate();
            $newDate->setChallenge($newChallenge);
            $newDate->setStartDate($dates->getStartDate()->modify("+2 months"));
            $newDate->setEndDate($dates->getEndDate()->modify("+2 months"));
            $newChallenge->addChallengeDate($newDate);
            $em->persist($newDate);
        }
        $newChallenge->setRegistrationOpening($challenge->getRegistrationOpening()->modify("+2 months"));
        $newChallenge->setRegistrationClosing($challenge->getRegistrationClosing()->modify("+2 months"));
        $em->persist($newChallenge);
        $em->flush();

        return $this->redirectToRoute('challenge_admin_index');
    }


    /**
     * @Route("/", name="challenge_admin_index", methods={"GET"})
     * @param Request $request
     * @param ChallengeRepository $challengeRepository
     * @param Calcul $paginationService
     * @return Response
     */
    public function index(Request $request, ChallengeRepository $challengeRepository, Calcul $paginationService): Response
    {
        $qb = $challengeRepository->createQueryBuilder('c')
            ->where("c.user IS NULL")
            ->orderBy('c.season', 'DESC')
            ->addOrderBy('c.registrationOpening', 'DESC')
            ->addOrderBy('c.id', 'DESC');
        $paginator = $paginationService->process($qb, $request);
        return $this->render('backend/challenge/index.html.twig', [
            'paginator' => $paginator,
        ]);
    }

    /**
     * @Route("/new", name="challenge_admin_new", methods={"GET","POST"})
     * @param Request $request
     * @param SluggerInterface $slugger
     * @param UserRepository $userRepository
     * @return Response
     */
    public function new(Request $request, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        return $this->edit($request, new Challenge(), $slugger, $userRepository);
    }

    /**
     * @Route("/delete-participation/{id}", name="challenge_admin_delete_participation", methods={"GET","POST"})
     * @param Participation $participation
     * @return Response
     */
    public function deleteParticipation(Participation $participation): Response
    {
        try {
            $participation->setChallenge(null);
            $em = $this->getDoctrine()->getManager();
            $em->remove($participation);
            $em->flush();
        } catch (Exception $e) {
            VarDumper::dump($e);
        }
        return new JsonResponse([
            'success' => true,
            'replace' => ""
        ]);
    }

    /**
     * @Route("/toggle-participation/{id}", name="challenge_admin_toggle_participation", methods={"GET","POST"})
     * @param Request $request
     * @param Participation $participation
     * @return Response
     */
    public function toggleParticipation(Participation $participation, Swift_Mailer $mailer): Response
    {
        $participation->setEnabled(!$participation->getEnabled());
        $this->getDoctrine()->getManager()->flush();
        $message = (new Swift_Message('Validation de votre inscription au challenge ' . $participation->getChallenge()->getTitle()))
            ->setFrom($this->getParameter('webmaster_email'))
            ->setTo($participation->getUser()->getEmail())
            ->setBody(
                $this->renderView(
                // templates/emails/registration.html.twig
                    "mails/challenge/validated.html.twig",
                    ['challenge' => $participation->getChallenge()]
                ),
                'text/html'
            )

            // you can remove the following code if you don't define a text version for your emails
            ->addPart(
                $this->renderView(
                // templates/emails/registration.txt.twig
                    "mails/challenge/validated.html.twig",
                    ['challenge' => $participation->getChallenge()]
                ),
                'text/plain'
            );
        try {

            $mailer->send($message);
        } catch (Exception $e) {

        }
        return new JsonResponse([
            'success' => true,
            'replace' => $participation->getEnabled() ? "<i class='fas fa-check text-success'></i>" : "<i class='fas fa-times text-danger'></i>"
        ]);
    }

    /**
     * @Route("/{id}/edit", name="challenge_admin_edit", methods={"GET","POST"})
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
        $originalRules = new ArrayCollection();
        foreach ($challenge->getRules() as $rule) {
            $originalRules->add($rule);
        }
        $originalPrizes = new ArrayCollection();
        foreach ($challenge->getChallengePrizes() as $prize) {
            $originalPrizes->add($prize);
        }
        $form = $this->createForm(ChallengeType::class, $challenge, ['attr' => ['novalidate' => 'novalidate']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $challenge->setTranslatableLocale($request->getLocale());

            $banner = $form->get('banner')->getData();
            $theFile = $form->get('theFile')->getData();
            foreach ($originalDates as $date) {
                if (false === $challenge->getChallengeDates()->contains($date)) {
                    $date->setChallenge(null);
                    $entityManager->persist($date);
                    $entityManager->remove($date);
                }
            }
            /** @var Rule $rule */
            foreach ($originalRules as $rule) {
                if (false === $challenge->getRules()->contains($rule)) {
                    $challenge->removeRule($rule);
                    $rule->removeChallenge($challenge);

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

            if ($theFile) {
                $originalFilename = pathinfo($theFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $theFile->guessExtension();
                try {
                    $theFile->move(
                        $this->getParameter('challenge_file_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $challenge->setTheFile($newFilename);
            }
            foreach ($challenge->getRules() as $rule) {
                $rule->addChallenge($challenge);
                $entityManager->persist($rule);
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
     * @Route("/arbitre/change/{id}", name="change_arbitre")
     * @param Request $request
     * @param Participation $participation
     */
    public function changeArbitre(Request $request, Participation $participation, UserRepository $userRepository)
    {
        if($request->get('arbitre', null)) {
            $arbitre = $userRepository->find($request->get('arbitre'));
            if ($arbitre != null) {
                $participation->setArbitre($arbitre);
                $this->getDoctrine()->getManager()->flush();
                return new JsonResponse([
                    'success' => true,
                    "message" => ""
                ]);
            } else {
                $participation->setArbitre(null);
                $this->getDoctrine()->getManager()->flush();

            }
        }
        if($request->get('openChallenge', null)){
            $participation->setOpenChallenge(new \DateTime($request->get('openChallenge', null)));
            $this->getDoctrine()->getManager()->flush();
            return new JsonResponse([
                'success' => true,
                "message" => ""
            ]);
        }
        if($request->get('closeChallenge')){
            $participation->setCloseChallenge(new \DateTime($request->get('closeChallenge', null)));
            $this->getDoctrine()->getManager()->flush();
            return new JsonResponse([
                'success' => true,
                "message" => ""
            ]);
        }

        return new JsonResponse([
            'success' => false,
            "message" => "Arbitre non trouvé."
        ]);
    }

    /**
     * @Route("/{id}", name="challenge_admin_delete", methods={"GET"})
     * @param Challenge $challenge
     * @return Response
     */
    public function delete(Challenge $challenge): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        foreach ($challenge->getParticipations() as $participation) {
            $entityManager->remove($participation);
        }
        foreach ($challenge->getRuns() as $run) {
            $entityManager->remove($run);
        }
        foreach ($challenge->getRules() as $rule) {
            $challenge->removeRule($rule);
        }
        foreach ($challenge->getChallengePrizes() as $prize) {
            $entityManager->remove($prize);
        }
        foreach ($challenge->getChallengeDates() as $date) {
            $entityManager->remove($date);
        }
        foreach ($challenge->getChallengeSettings() as $setting) {
            $entityManager->remove($setting);
        }
        foreach ($challenge->getChallengeNewsletters() as $newsletter) {
            $entityManager->remove($newsletter);
        }

        $entityManager->remove($challenge);

        $entityManager->flush();

        return $this->redirectToRoute('challenge_admin_index');
    }

    /**
     * @Route("/add-participation/{id}", name="add_participation")
     * @param Request $request
     * @param Challenge $challenge
     * @param UserRepository $userRepository
     * @return Response
     */
    public function addParticipation(Request $request, Challenge $challenge, UserRepository $userRepository)
    {
        foreach ($request->get('participations',[]) as $userId) {
            $user = $userRepository->find($userId);
            $participation = new Participation();
            $participation
                ->setChallenge($challenge)
                ->setEnabled(true)
                ->setUser($user);
            $entityManger = $this->getDoctrine()->getManager();
            $entityManger->persist($participation);
            $entityManger->flush();
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
        return $this->render("backend/challenge/participations.html.twig", ["challenge" => $challenge,
            'arbitres' => $arbitres,
            'availablePlayer' => $availablePlayer,]);
    }
}
