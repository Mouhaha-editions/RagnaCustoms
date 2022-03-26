<?php

namespace App\Tests;

use App\Repository\SongRepository;
use App\Service\SongService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestTest extends KernelTestCase
{
    public function testSomething()
    {
        self::bootKernel([
            'environment' => 'test',
            'debug'       => true,
        ]);

        $this->assertContains(true,[false,'1',true]);
    }

    public function testSomethingElse()
    {
        self::bootKernel([
            'environment' => 'tests',
            'debug'       => false,
        ]);
        $this->assertContains(false,[false,'1',true]);
    }
}
