<?php

namespace App\Service;

use VisualAppeal\Matomo;

class MatomoService
{
    private Matomo $singleton;

    public function __construct()
    {
        $this->singleton = new Matomo("https://matomo.ragnacustoms.com", "e19493332a26e28b4f3a57ea4f856039", "1");
        $this->singleton->reset();
    }

    public function getUrlStat($url)
    {
        $views = 0;
        $url = str_replace('https://127.0.0.1:8000', "https://ragnacustoms.com", $url);
        $this->singleton->setRange("2021-01-01", (new \DateTime())->format('Y-m-d'));
        //$this->singleton->setPeriod( Matomo::PERIOD_MONTH); //All data from the first to the last date
        $this->singleton->setFormat(Matomo::FORMAT_JSON);
        $result = $this->singleton->getPageUrl($url);

        foreach ($result as $res) {
            if (isset($res[0])) {
                $views += $res[0]->nb_visits;
            }
        }
        return $views;
    }
}