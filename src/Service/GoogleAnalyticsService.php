<?php

namespace App\Service;

use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Symfony\Component\VarDumper\VarDumper;

class GoogleAnalyticsService
{
    /**
     * @var Google_Service_AnalyticsReporting
     */
    private $analytics;
    /**
     * @var Google_Client
     */
    private $client;
    private $file ;
    /** @var string  */
    private $viewId;

    public function __construct(string $file,string $viewId)
    {
        $this->viewId = $viewId;
        $this->client = new Google_Client();
        $this->client->setApplicationName("Hello Analytics Reporting");
        $this->client->setAuthConfig($file);
        $this->client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->analytics = new Google_Service_AnalyticsReporting($this->client);
    }


    public function getStats()
    {
        // Replace with your view ID, for example XXXX.
        $VIEW_ID = $this->viewId;

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("7daysAgo");
        $dateRange->setEndDate("today");

        // Create the Metrics object.
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($sessions));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        VarDumper::dump($this->analytics->reports->batchGet( $body ));
    }
}

