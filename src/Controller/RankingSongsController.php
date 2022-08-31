<?php

namespace App\Controller;

use App\Entity\DifficultyRank;
use App\Entity\Playlist;
use App\Entity\Score;
use App\Entity\Song;
use App\Entity\SongDifficulty;
use App\Entity\SongTemporaryList;
use App\Entity\Utilisateur;
use App\Entity\Vote;
use App\Form\AddPlaylistFormType;
use App\Form\VoteType;
use App\Repository\DownloadCounterRepository;
use App\Repository\ScoreRepository;
use App\Repository\SongCategoryRepository;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Repository\VoteCounterRepository;
use App\Service\DiscordService;
use App\Service\DownloadService;
use App\Service\GoogleAnalyticsService;
use App\Service\ScoreService;
use App\Service\SongService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;


class RankingSongsController extends AbstractController
{
    private $paginate = 30;

    /**
     * @Route("/ranking-song/", name="ranking_song")
     */
    public function library(Request $request, DiscordService $discordService, SongDifficultyRepository $songDifficultyRepository): Response
    {
        $form = $this->createFormBuilder();
        $form->add('songs', EntityType::class, [
            'class' => Song::class,
            'multiple' => true,
            'choice_label' => function (Song $song) {
                return ($song->isRanked() ? "[R] ":"").$song->getName();
            },
            "attr" => [
                'class' => "select2"
            ],
            "query_builder" => function (SongRepository $er) {
                return $er->createQueryBuilder('s')
                    ->orderBy("s.name", "ASC");
            }
        ]);
        $form->add('rank_unrank', SubmitType::class);
        $form = $form->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Song $song */
            foreach ($form->get('songs')->getData() as $song) {
                foreach ($song->getSongDifficulties() as $diff) {
                    $diff->setIsRanked(!$diff->isRanked());
                    $songDifficultyRepository->add($diff, true);
                }
                $discordService->rankedSong($song);
            }
            $this->addFlash('success','Songs ranked or unranked');
        return $this->redirectToRoute('ranking_song');
        }

        return $this->renderForm('ranking_song/index.html.twig', [
            'form' => $form
        ]);
    }


}
