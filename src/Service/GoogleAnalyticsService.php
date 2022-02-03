<?php

namespace App\Service;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Symfony\Component\VarDumper\VarDumper;

class GoogleAnalyticsService
{


    /**
     * @var BetaAnalyticsDataClient
     */
    private $client;
    private $file ;
    /** @var string  */
    private $viewId;

    public function __construct(string $file,string $viewId)
    {
        $this->viewId = $viewId;
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$file);
        $this->client = new BetaAnalyticsDataClient();

    }


    public function getStats()
    {
        // Replace with your view ID, for example XXXX.

        $response = $this->client->runReport([
            'property' => 'properties/268384948',
            'dateRanges' => [
                new DateRange([
                    'start_date' => (new \DateTime())->modify("-30 days")->format('Y-m-d'),
                    'end_date' => 'today',
                ]),
                ],
                'dimensions' => [new Dimension(
                    [
                        'name' => 'customEvent:download_1',
                    ]
                ),
                ],
                'metrics' => [new Metric(
                    [
                        'name' => 'eventCount',
                    ]
                )
            ],
        ]);
        VarDumper::dump($response->getRows()->offsetGet(0));
    }
}

