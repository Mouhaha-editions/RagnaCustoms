<?php

namespace App\Controller;

use App\Entity\Friend;
use App\Entity\Utilisateur;
use App\Repository\FriendRepository;
use Pkshetlie\PaginationBundle\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FriendRequestController extends AbstractController
{
    #[Route('/friend/request/{id}', name: 'app_friend_request')]
    public function request(Utilisateur $requestedUser, FriendRepository $friendRepository): Response
    {
        if (!$this->isGranted('ROLE_USER') || $this->getUser()->getId() === $requestedUser->getId()) {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        if ($user->isFriendWith($requestedUser) !== Friend::STATE_NOT_REQUESTED) {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }

        $friendRequest = new Friend();
        $friendRequest->setUser($user);
        $friendRequest->setFriend($requestedUser);
        $friendRequest->setState(Friend::STATE_REQUESTED);
        $friendRepository->add($friendRequest);

        return new JsonResponse([
            'success' => true,
            'result' => $this->renderView('user/partial/friend_request.html.twig', ['user' => $requestedUser])
        ]);
    }

    #[Route('/friend/remove/{id}', name: 'app_friend_request_remove')]
    public function remove(Utilisateur $requestedUser, FriendRepository $friendRepository): Response
    {
        if (!$this->isGranted('ROLE_USER') || $this->getUser()->getId() === $requestedUser->getId()) {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        $isFriendWith = $user->isFriendWith($requestedUser);
        if ($isFriendWith == Friend::STATE_NOT_REQUESTED || $isFriendWith == Friend::STATE_REFUSED) {
            return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
        }

        $friendRequest = $friendRepository->findOneBy(['user' => $user, 'friend' => $requestedUser]);
        $isRequester = true;

        if (!$friendRequest) {
            $friendRequest = $friendRepository->findOneBy(['user' => $requestedUser, 'friend' => $user]);

            if ($friendRequest) {
                $isRequester = false;
            } else {
                return new JsonResponse([], Response::HTTP_UNAUTHORIZED);
            }
        }

        if ($isRequester) {
            $friendRepository->remove($friendRequest);
        } else {
            $friendRequest->setState(Friend::STATE_REFUSED);
            $friendRepository->add($friendRequest);
        }

        return new JsonResponse([
            'success' => true,
            'result' => $this->renderView('user/partial/friend_request.html.twig', ['user' => $requestedUser])
        ]);
    }

    #[Route('/friend/accept/{id}', name: 'app_friend_request_accept')]
    public function accept(Friend $friend, FriendRepository $friendRepository): Response
    {
        if (!$this->isGranted('ROLE_USER') || $this->getUser()->getId() !== $friend->getFriend()->getId()) {
            return $this->redirectToRoute('home');
        }

        $friend->setState(Friend::STATE_ACCEPTED);
        $friendRepository->add($friend);

        $this->addFlash('success', 'Friend request accepted!');

       return $this->redirectToRoute('app_friend_list');
    }

    #[Route('/friend/refuse/{id}', name: 'app_friend_request_refuse')]
    public function refused(Friend $friend, FriendRepository $friendRepository): Response
    {
        if (!$this->isGranted('ROLE_USER') || ($this->getUser() !== $friend->getUser() && $this->getUser() !== $friend->getFriend())) {
            return $this->redirectToRoute('home');
        }

        $isRequester = true;
        if($friend->getFriend() === $this->getUser()){
            $isRequester = false;
        }

        if ($isRequester) {
            $friendRepository->remove($friend);
        } else {
            $friend->setState(Friend::STATE_REFUSED);
            $friendRepository->add($friend);
        }
        $this->addFlash('success', 'Friend request refused!');

        return $this->redirectToRoute('app_friend_list');
    }

    #[Route('/friend/list', name: 'app_friend_list')]
    public function list(
        Request $request,
        FriendRepository $friendRepository,
        PaginationService $paginationService
    ): Response {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();
        $qb = $friendRepository->createQueryBuilder('f')
            ->leftJoin('f.user', 'user')
            ->leftJoin('f.friend', 'friend')
            ->andWhere('f.state = :accepted')
            ->setParameter('accepted', Friend::STATE_ACCEPTED);
        $qb
            ->andWhere($qb->expr()->orX('user.id = :user', 'friend.id  = :user'))
            ->setParameter('user', $user)
            ->orderBy('IF(user.id = :user, friend.username, user.username)', 'ASC');
        $friends = $paginationService->setDefaults(50)->process($qb, $request);

        return $this->render('friend_request/index.html.twig', [
            'friends' => $friends,
        ]);
    }
}
