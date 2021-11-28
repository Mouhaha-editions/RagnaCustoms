<?php


namespace App\Controller;


use App\Entity\ChallengeSetting;
use App\Entity\Run;
use App\Service\RunService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{

    /**
     * @Route("/api/user/{apiKey}",name="api_points",methods={"POST"})
     */
    public function api(Request $request, string $apiKey, RunService $runService)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        /** @var Run $run */
        $run = $entityManager->getRepository(Run::class)
            ->createQueryBuilder('r')
            ->leftJoin("r.challenge", 'challenge')
            ->leftJoin("challenge.challengeDates", 'challenge_dates')
            ->leftJoin("r.user", 'user')
            ->where('user.apiKey = :apikey')
            ->andWhere('challenge_dates.startDate <= :now')
            ->andWhere('challenge_dates.endDate >= :now')
            ->setParameter("apikey", $apiKey)
            ->setParameter("now", new DateTime())
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->setFirstResult(0)
            ->getQuery()->getOneOrNullResult();

        if($run == null){
            $run = $entityManager->getRepository(Run::class)
                ->createQueryBuilder('r')
                ->leftJoin("r.user", 'user')
                ->where('user.apiKey = :apikey')
                ->andWhere('r.training = true')
                ->andWhere('r.training_open = true')
                ->setParameter("apikey", $apiKey)
                ->orderBy('r.id', 'DESC')
                ->setMaxResults(1)
                ->setFirstResult(0)
                ->getQuery()->getOneOrNullResult();
        }
        $results = [];


        if ($run != null) {
            $score = (array)json_decode($request->getContent());
            $countEtape = 0;
            $etapeDone = 0;

            foreach ($run->getRunSettings() as $runSetting) {
                $isStep = false;
                $isStepDone = false;
                if (isset($score[$runSetting->getChallengeSetting()->getAutoValue()])) {
                    $runSetting->setValue($score[$runSetting->getChallengeSetting()->getAutoValue()]);
                    $entityManager->flush();
                }
                if($runSetting->getChallengeSetting()->getIsStepToVictory()){
                    $countEtape++;
                    $isStep = true;
                    $min = $runSetting->getChallengeSetting()->getStepToVictoryMin() == null ? -999999 : $runSetting->getChallengeSetting()->getStepToVictoryMin() ;
                    $max = $runSetting->getChallengeSetting()->getStepToVictoryMax() == null ? 999999 : $runSetting->getChallengeSetting()->getStepToVictoryMax() ;

                    if($runSetting->getValue() <= $max && $runSetting->getValue() >= $min){
                        $etapeDone++;
                        $isStepDone = true;
                    }
                }
                if($runSetting->getChallengeSetting()->getSendToMod()){
                    $min = $runSetting->getChallengeSetting()->getStepToVictoryMin() == null ? -999999 : $runSetting->getChallengeSetting()->getStepToVictoryMin() ;

                    $results[] = [
                        "text"=>$runSetting->getChallengeSetting()->getLabel(),
                        "value"=>$runSetting->getChallengeSetting()->getInputType() == ChallengeSetting::CHECKBOX ? null  :
                            ($isStep ? "req. ".$min :
                                (is_numeric($runSetting->getValue()) ? ceil(floatval($runSetting->getValue())):$runSetting->getValue())),
                        "score"=>$runSetting->getChallengeSetting()->getInputType() == ChallengeSetting::CHECKBOX ? ($runSetting->getValue() ? "oui":"non") : ceil(floatval($runSetting->getValue()) * $runSetting->getChallengeSetting()->getRatio()),
                        "isStepToVictory"=>$isStep,
                        "isTotal"=>false,
                        "color"=>!$isStep ? "#FBB03BFF" :( !$isStepDone ? "#FF0000ff": "#00FF00FF"),
                    ];
                }
            }
          if($countEtape != 0){
              $results[] = [
                "text"=>"Etapes vers la victoire",
                "value"=>null,
                "score"=>$etapeDone."/".$countEtape,
                "isStepToVictory"=>false,
                "isTotal"=>true,
                "color"=> "#FBB03BFF",
            ];
        }
            $runService->ComputeScore($run);
            if($run->getChallenge()->getDisplayTotalInMod()) {
                $results[] = [
                    "text" => "Total",
                    "value" => null,
                    "score" => $run->getComputedScore(),
                    "isStepToVictory" => false,
                    "isTotal" => true,
                    "color" => "#FBB03BFF",
                ];
            }
            $entityManager->flush();
        }else{
            return new Response("",500);
        }
        return new JsonResponse($results);
    }

    /**
     * @Route("/api/end-run/{apiKey}",name="api_end_run",methods={"POST"})
     */
    public function apiEndRun(string $apiKey, RunService $runService)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();

        /** @var Run $run */
        $run = $entityManager->getRepository(Run::class)
            ->createQueryBuilder('r')
            ->leftJoin("r.challenge", 'challenge')
            ->leftJoin("challenge.challengeDates", 'challenge_dates')
            ->leftJoin("r.user", 'user')
            ->where('user.apiKey = :apikey')
            ->andWhere('challenge_dates.startDate >= :now')
            ->andWhere('challenge_dates.endDate <= :now')
            ->setParameter("apikey", $apiKey)
            ->setParameter("now", new DateTime())
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(1)
            ->setFirstResult(0)
            ->getQuery()->getOneOrNullResult();
        if ($run != null) {
            $runService->endOfRun($run);
        }
        return new Response('OK');
    }
}