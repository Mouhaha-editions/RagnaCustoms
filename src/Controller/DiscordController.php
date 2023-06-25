<?php

namespace App\Controller;


use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

class DiscordController extends AbstractController
{
    #[Route(path: '/discord-link', name: 'discord_link')]
    public function Discord(ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', 'You need to be logged to link your discord account.');
            return $this->redirectToRoute('home');
        }

        $provider = new Discord([
            'clientId'     => $this->getParameter('discord_client_id'),
            'clientSecret' => $this->getParameter('discord_client_secret'),
            'redirectUri'  => $this->generateUrl("discord_link", [], UrlGenerator::ABSOLUTE_URL)
        ]);

        if (!isset($_GET['code'])) {
            // Step 1. Get authorization code
            $options = [
                'scope' => [
                    'identify',
                    'email',
                    'guilds.members.read'
                ]
            ];
            $authUrl = $provider->getAuthorizationUrl($options);
            $_SESSION['oauth2state'] = $provider->getState();

            return $this->redirect($authUrl);
            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            $this->addFlash('danger', 'Unvalid State.');
            return $this->redirectToRoute('home');
        } else {

            // Step 2. Get an access token using the provided authorization code
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            try {
                /** @var DiscordResourceOwner $DiscordUser */
                $DiscordUser = $provider->getResourceOwner($token);

                /** @var Utilisateur $user */
                $user = $this->getUser();
                $user->setDiscordUsername($DiscordUser->getUsername() . "#" . $DiscordUser->getDiscriminator());
                $user->setDiscordId($DiscordUser->getId());
                $user->setDiscordEmail($DiscordUser->getEmail());
                $user->setAuthToken($token->getToken());
                $user->setAuthTokenRefresh($token->getRefreshToken());

                $doctrine->getManager()->flush();
                $client = new Client();

                $response = $client->request('GET', "https://discord.com/api/v9/guilds/824960946404327450/members/{$user->getDiscordId()}", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $user->getAuthToken(),
                        'Accept' => 'application/json'
                    ]
                ]);


                if ($response->getStatusCode() === 200) {
                    $memberData = json_decode($response->getBody(), true);
                    $roles = $memberData['roles'];

                    $x = in_array('helping viking', $roles);
                    // Faites ce que vous voulez avec les informations sur les rôles de l'utilisateur
                    foreach ($roles as $roleId) {
                        // Vous pouvez récupérer les détails du rôle en effectuant une autre requête à l'API Discord
                        // GET /guilds/{guild_id}/roles/{role_id}
                    }
                }


                $this->addFlash('success', 'Account linked with Discord');
            } catch (Exception $e) {

                // Failed to get user details
                $this->addFlash('danger', "Cannot link your account with discord");

            }
        }
        return $this->redirectToRoute('user');
    }

    /**
     * @return Response
     */
    #[Route('/discord/redirect-auth', name: 'discord_redirect_authorization')]
    public function redirectAuthorizationAction(): Response
    {
        $provider = new Discord([
            'clientId'     => $this->getParameter('discord_client_id'),
            'clientSecret' => $this->getParameter('discord_client_secret'),
            'redirectUri'  => $this->generateUrl("discord_link", [], UrlGenerator::ABSOLUTE_URL)
        ]);

        $options = [
            'scope' => [
                'identify',
                'email',
                'guilds'
            ]
        ];
        $authUrl = $provider->getAuthorizationUrl($options);
        $_SESSION['oauth2state'] = $provider->getState();
        return $this->redirect($authUrl);
    }

    /**
     * @return void
     */
    #[Route(name: 'discord_set_token', path: 'set_token')]
    public function setTokenAction(): void
    {
        // empty as authenticator will handle the request
    }


    #[Route(path: '/discord-link', name: 'discord_check')]
    public function DiscordCheck(ManagerRegistry $doctrine): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('danger', 'You need to be logged to link your discord account.');
            return $this->redirectToRoute('home');
        }

        $provider = new Discord([
            'clientId'     => $this->getParameter('discord_client_id'),
            'clientSecret' => $this->getParameter('discord_client_secret'),
            'redirectUri'  => $this->generateUrl("discord_link", [], UrlGenerator::ABSOLUTE_URL)
        ]);

        if (!isset($_GET['code'])) {
            // Step 1. Get authorization code
            $options = [
                'scope' => [
                    'identify',
                    'email',
                    'guilds'
                ]
            ];
            $authUrl = $provider->getAuthorizationUrl($options);
            $_SESSION['oauth2state'] = $provider->getState();
            header('Location: ' . $authUrl);

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            $this->addFlash('danger', 'Unvalid State.');
            return $this->redirectToRoute('home');
        } else {

            // Step 2. Get an access token using the provided authorization code
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            try {
                /** @var DiscordResourceOwner $DiscordUser */
                $DiscordUser = $provider->getResourceOwner($token);

                /** @var Utilisateur $user */
                $user = $this->getUser();
                $user->setDiscordUsername($DiscordUser->getUsername() . "#" . $DiscordUser->getDiscriminator());
                $user->setDiscordId($DiscordUser->getId());
                $user->setDiscordEmail($DiscordUser->getEmail());

                $doctrine->getManager()->flush();
                $this->addFlash('success', 'Account linked with Discord');
            } catch (Exception $e) {

                // Failed to get user details
                $this->addFlash('danger', "Cannot link your account with discord");

            }
        }
        return $this->redirectToRoute('user');
    }

}
