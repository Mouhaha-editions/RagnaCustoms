<?php

namespace App\Service;

use Patreon\API;
use Patreon\OAuth;
use Symfony\Component\VarDumper\VarDumper;

readonly class PatreonService
{
    private mixed $accessToken;

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {

    }

    private function checkAndRefreshToken()
    {
        $file = 'token.json';
        $data = json_decode(file_get_contents($file),true);

        $oauth_client = new OAuth($this->clientId, $this->clientSecret);
        // Get a fresher access token
        $tokens = $oauth_client->refresh_token($data['refresh_token'], null);

        $file = 'token.json';
        file_put_contents($file, json_encode($tokens));

        if ($tokens['access_token']) {
            $this->accessToken = $tokens['access_token'];
        } else {
           throw new \Exception("Can't recover from access failure\n");
        }
    }

    public function getCampaigns()
    {
        $this->checkAndRefreshToken();

        $api_client = new API($this->accessToken);

        $campaign_response = $api_client->fetch_campaigns();

        $campaignId = $campaign_response['data']['0']['id'];
        $campaign  = $api_client->fetch_campaign_details($campaignId);
        $member_response = $api_client->fetch_page_of_members_from_campaign($campaignId, 500);

foreach($member_response['data'] AS $members){
$member = $api_client->fetch_member_details($members['id']);
VarDumper::dump($campaign);
VarDumper::dump($member);die;
}
        dd($campaign_response);


        $user = $campaign->relationship('creator')->resolve($campaign_response);
        echo "user is\n";
        print_r($user->asArray(true));
    }
}

