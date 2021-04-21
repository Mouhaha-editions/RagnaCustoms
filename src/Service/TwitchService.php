<?php

namespace App\Service;

use App\Entity\Song;
use TwitchApi\TwitchApi;

class TwitchService
{

    /** @var string */
    private $applicationId;
    /** @var string */
    private $clientSecret;
    /** @var TwitchApi $twitchApi */
    private static $twitchApi = null;
    private static $getCurrentChannels = null;

    public function __construct(string $applicationId, string $clientSecret)
    {
        if (self::$twitchApi == null) {
            self::$twitchApi = new TwitchApi([
                'client_id' => $applicationId,
            ]);
        }
    }

    public function getCurrentStreamindChannels()
    {
        if(self::$getCurrentChannels == null) {
            self::$getCurrentChannels = self::$twitchApi->getLiveStreams(null, "ragnaröck",null,"live",100);
        }
        return self::$getCurrentChannels['streams'];
    }
    public function countStreamers()
    {
        return count($this->getCurrentStreamindChannels());
    }
}

