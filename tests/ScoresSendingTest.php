<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScoresSendingTest extends WebTestCase
{
    public function testSomething(): void
    {
        $response = static::createClient([],['HTTP_HOST'       => '127.0.0.1:8000',])->request('GET', '/',[

        ]);

        $this->assertResponseIsSuccessful();
    }
}
