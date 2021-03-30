<?php

namespace App\Service;

use App\Entity\Song;

class DiscordService
{

    /** @var string */
    private $webhookUrl;

    public function __construct(string $webhookUrl)
    {

        $this->webhookUrl = $webhookUrl;
    }

    public function sendNewSongMessage(Song $song)
    {
        $timestamp = date("c", strtotime("now"));

        $json_data = json_encode([
            // Message
            "content" => "Hi vikings, there is a new map",

            // Username
            "username" => "RagnaCustoms",

            // Text-to-speech
            "tts" => false,

            // File upload
            // "file" => "",

            // Embeds Array
            "embeds" => [
                [
                    // Embed Title
                    "title" => "New Song - ".$song->getName()." by ".$song->getAuthorName(),

                    // Embed Type
                    "type" => "rich",

                    // Embed Description
//                    "description" => "",

                    // URL of title link
                    "url" => "https://ragnasong.com/",

                    // Timestamp of embed must be formatted as ISO8601
                    "timestamp" => $timestamp,

                    // Embed left border color in HEX
                    "color" => hexdec("3366ff"),

                    // Footer
//                    "footer" => [
//                        "text" => "GitHub.com/Mo45",
//                        "icon_url" => "https://ru.gravatar.com/userimage/28503754/1168e2bddca84fec2a63addb348c571d.jpg?size=375"
//                    ],

                    // Image to send
                    "image" => [
                        "url" => "https://ragnacustoms.com/covers/".$song->getId().$song->getCoverImageExtension()
                    ],

                    // Thumbnail
                    //"thumbnail" => [
                    //    "url" => "https://ru.gravatar.com/userimage/28503754/1168e2bddca84fec2a63addb348c571d.jpg?size=400"
                    //],

                    // Author
//                    "author" => [
//                        "name" => "krasin.space",
//                        "url" => "https://krasin.space/"
//                    ],

                    // Additional Fields array
                    "fields" => [
//                        // Field 1
                        [
                            "name" => "Mapper",
                            "value" => $song->getLevelAuthorName(),
                            "inline" => true
                        ],
//                        // Field 2
                        [
                            "name" => "Difficulties",
                            "value" => $song->getSongDifficultiesStr(),
                            "inline" => true
                        ],
//                        // Field 2
                        [
                            "name" => "Duration",
                            "value" => $song->getApproximativeDuration(),
                            "inline" => true
                        ]
//                        // Etc..
                    ]
                ]
            ]

        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


        $ch = curl_init($this->webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        curl_close($ch);
    }
}

