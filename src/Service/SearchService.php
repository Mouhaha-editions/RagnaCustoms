<?php

namespace App\Service;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;


readonly class SearchService
{
    public function __construct(private Security $security)
    {

    }

    public function baseSearchQb(QueryBuilder $qb, Request $request): array
    {
        $appliedFilters = [];

        $qb
            ->leftJoin('song.mappers', 'mapper')
            ->leftJoin('song.categoryTags', 'tag');

        if ($request->get('only_ranked')) {
            $qb->andWhere('song_difficulties.isRanked = true');
            $appliedFilters[] = 'only ranked';
        }

        if ($request->get('downloads_filter_difficulties')) {
            $qb->leftJoin('song_difficulties.difficultyRank', 'rank');

            switch ($request->get('downloads_filter_difficulties')) {
                case 1:
                    $qb->andWhere($qb->expr()->between('rank.level', 1, 3));
                    $appliedFilters[] = 'lvl 1 to 3';

                    break;
                case 2 :
                    $qb->andWhere($qb->expr()->between('rank.level', 4, 7));
                    $appliedFilters[] = 'lvl 4 to 7';
                    break;
                case 3 :
                    $qb->andWhere($qb->expr()->between('rank.level', 8, 10));
                    $appliedFilters[] = 'lvl 8 to 10';
                    break;
                case 6 :
                    $qb->andWhere('rank.level > 10');
                    $appliedFilters[] = 'lvl over 10';
                    break;
            }
        }

        $categories = $request->get('downloads_filter_categories');

        if ($categories != null) {
            foreach ($categories as $k => $v) {
                $qb
                    ->andWhere('tag.id = :tag'.$k)
                    ->setParameter('tag'.$k, $v);
            }
            $appliedFilters[] = 'categories spécifiques';
        }

        if ($request->get('converted_maps')) {
            switch ($request->get('converted_maps')) {
                case 1:
                    $qb->andWhere('(song.converted = false OR song.converted IS NULL)');
                    $appliedFilters[] = 'hide converted';
                    break;
                case 2 :
                    $qb->andWhere('song.converted = true');
                    $appliedFilters[] = 'only converted';

                    break;
            }
        }

        if ($request->get('wip_maps')) {
            switch ($request->get('wip_maps')) {
                case 1:
                    //with
                    $appliedFilters[] = 'display W.I.P.';
                    break;
                case 2 :
                    //only
                    $qb->andWhere('song.wip = true');
                    $appliedFilters[] = 'only W.I.P.';
                    break;
                default:
                    $qb->andWhere('song.wip != true');
                    break;
            }
        } else {
            $qb->andWhere('song.wip != true');
        }

        if ($request->get('downloads_submitted_date')) {
            switch ($request->get('downloads_submitted_date')) {
                case 1:
                    $qb
                        ->andWhere('song.lastDateUpload >= :last7days')
                        ->setParameter('last7days', (new DateTime())->modify('-7 days'));
                    $appliedFilters[] = 'last 7 days';
                    break;
                case 2 :
                    $qb
                        ->andWhere('(song.lastDateUpload >= :last15days)')
                        ->setParameter('last15days', (new DateTime())->modify('-15 days'));
                    $appliedFilters[] = 'last 15 days';
                    break;
                case 3 :
                    $qb
                        ->andWhere('(song.lastDateUpload >= :last45days)')
                        ->setParameter('last45days', (new DateTime())->modify('-45 days'));
                    $appliedFilters[] = 'last 45 days';
                    break;
            }
        }

        if ($request->get('mapped_for')) {
            switch ($request->get('mapped_for')) {
                case 2:
                    $qb
                        ->andWhere('(song.bestPlatform LIKE :platform)')
                        ->setParameter('platform', '%1%');
                    $appliedFilters[] = 'Mapped for Viking on Tour';
                    break;
                case 1 :
                    $qb
                        ->andWhere('(song.bestPlatform LIKE :platform)')
                        ->setParameter('platform', '%0%');
                    $appliedFilters[] = 'Mapped for VR';
                    break;
            }
        }

        if ($request->get('not_downloaded', 0) > 0 && $this->security->isGranted('ROLE_USER')) {
            $qb->leftJoin('song.downloadCounters', 'download_counters')
                ->addSelect('SUM(IF(download_counters.user = :user,1,0)) AS HIDDEN count_download_user')
                ->andHaving('count_download_user = 0')
                ->setParameter('user', $this->security->getUser());
            $appliedFilters[] = 'not downloaded';
        }

        $qb->andWhere('song.moderated = true');
        $qb->andWhere('song.active = true')
            ->andWhere('(song.programmationDate <= :now AND song.programmationDate IS NOT NULL)')
            ->setParameter('now', new DateTime());
        //get the 'type' param (added for ajax search)
        $type = $request->get('type');
        //check if this is an ajax request
        $ajaxRequest = $type == 'ajax';
        //remove the 'type' parameter so pagination does not break
        if ($ajaxRequest) {
            $request->query->remove('type');
        }

        if ($request->get('search')) {
            $exp = explode(':', $request->get('search'));

            if (count($exp) == 1) {
                if ($request->get('searchBy')) {
                    $exp[1] = $exp[0];
                    $exp[0] = $request->get('searchBy');
                }
            }

            $appliedFilters[] = 'search: "'.implode(' -> ', $exp).'"';

            switch ($exp[0]) {
                case 'mapper':
                    if (count($exp) >= 2) {
                        $qb
                            ->andWhere('(mapper.mapper_name LIKE :search_string)')
                            ->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
//                case 'category':
//                    if (count($exp) >= 1) {
//                        $qb->andWhere('(song.songCategory = :category)')
//                            ->setParameter('category', $exp[1] == '' ? null : $exp[1]);
//                    }
//                    break;
                case 'genre':
                    if (count($exp) >= 2) {
                        $qb
                            ->andWhere('(tag.label LIKE :search_string)')
                            ->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                case 'artist':
                    if (count($exp) >= 2) {
                        $qb
                            ->andWhere('(song.authorName LIKE :search_string)')
                            ->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                case 'title':
                    if (count($exp) >= 2) {
                        $qb
                            ->andWhere('(song.name LIKE :search_string)')
                            ->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                case 'desc':
                    if (count($exp) >= 2) {
                        $qb
                            ->andWhere('(song.description LIKE :search_string)')
                            ->setParameter('search_string', '%'.$exp[1].'%');
                    }
                    break;
                default:
                    $searchString = explode(' ', $request->get('search'));
                    foreach ($searchString as $key => $search) {
                        $qb
                            ->andWhere(
                                $qb->expr()->orX(
                                    'song.name LIKE :search_string'.$key,
                                    'song.authorName LIKE :search_string'.$key,
                                    'song.description LIKE :search_string'.$key,
                                    'mapper.mapper_name LIKE :search_string'.$key,
                                    'tag.label LIKE :search_string'.$key
                                )
                            )
                            ->setParameter('search_string'.$key, '%'.$search.'%');
                    }
            }
        }

        $qb->andWhere('song.isDeleted != true');

        $order = $request->get('order_sort', 'asc') == 'asc' ? 'asc' : 'desc';
        switch ($request->get('order_by')) {
            case 'downloads':
                $qb->orderBy('song.downloads', $order);
                break;

            case 'upload_date':
                $qb->orderBy('song.lastDateUpload', $order);
                break;
            case 'name':
                $qb->orderBy('song.name', $order);
                break;
            case 'bpm':
                $qb->orderBy('song.beatsPerMinute', $order);
                break;
            case 'rating':
                $qb->orderBy('rating', $order);
                break;
            default:
                $qb->orderBy('song.lastDateUpload', 'DESC');
                break;
        }

        return $appliedFilters;
    }
}

