<?php


namespace App\Service;


use App\Entity\Challenge;
use App\Entity\Run;
use App\Entity\RunSettings;
use Doctrine\ORM\EntityManagerInterface;

class RunService
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Run $run
     * @return void
     */
    public function ComputeScore(Run $run, $computeOther = true)
    {
        $score = 0;
        $malusableScore = 0;

        $allruns = $this->em->getRepository(Run::class)
            ->createQueryBuilder('r')
            ->where('r.challenge = :challenge')
            ->andWhere('r.user = :user')
            ->andWhere('r.training = false')
            ->setParameter('user', $run->getUser())
            ->setParameter('challenge', $run->getChallenge())
            ->getQuery()
            ->getResult();


            foreach ($run->getRunSettings() as $setting) {
                if (!is_numeric($setting->getValue())) {
                    continue;
                }
                if ($setting->getChallengeSetting()->getIsUsedForScore()) {
                    if ($setting->getChallengeSetting()->getIsAffectedByMalus()) {
                        $malusableScore += ceil(floatval($setting->getValue()) * $setting->getChallengeSetting()->getRatio());
                    } else {
                        $score += ceil(floatval($setting->getValue()) * $setting->getChallengeSetting()->getRatio());
                    }
                    if ($setting->getChallengeSetting()->getIsReportedOnTheNextRun() && $computeOther) {
                        /** @var Run $r */
                        foreach ($allruns as $r) {
                            foreach ($r->getRunSettings() as $r_setting) {
                                if ($setting->getChallengeSetting() === $r_setting->getChallengeSetting() && $setting->getChallengeSetting()->getIsReportedOnTheNextRun()) {
                                    $r_setting->setValue(ceil($setting->getValue()));
                                    $this->ComputeScore($r, false);
                                }
                            }
                        }
                    }
                }
            $run->setScore(ceil($score));
        }
        $run->setComputedScore(ceil($score + ($malusableScore * (2-$run->getMalus()))));
    }

    public function endOfRun(Run $runToClose)
    {
        $runToClose->setEndDate(new \DateTime());
        $this->em->flush();
        $challenge = $runToClose->getChallenge();
        $user = $runToClose->getUser();
        $run = $this->em->getRepository(Run::class)->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.challenge  = :challenge')
            ->andWhere('r.endDate IS NULL')
            ->andWhere('r.training = false')
            ->setParameter('challenge', $challenge)
            ->setParameter('user', $user)
            ->setFirstResult(0)->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        /** @var Run $run */
        if ($run == null) {
            $run = new Run();
            $run->setChallenge($challenge);
            $run->setUser($user);
            $user->addRun($run);
            $this->em->persist($run);
            $run->setStartDate(new \DateTime());
            $countRun = $user->countRun($challenge);
            /** @var Run $lastrun */
            $lastrun = $this->em->getRepository(Run::class)->createQueryBuilder('r')
                ->where('r.user = :user')
                ->andWhere('r.challenge  = :challenge')
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
                $this->em->persist($runSetting);
            }
            $this->em->flush();
        }


    }
}