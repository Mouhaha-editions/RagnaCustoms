<?php

namespace App\Migrations\Factory;

use App\Contract\SongAwareMigrationInterface;
use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\SongService;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('doctrine.migrations.migrations_factory')]
class MigrationFactoryDecorator implements MigrationFactory
{
    private $migrationFactory;
    private $songDifficultyRepository;
    private $songRepository;
    private $songService;

    public function __construct(MigrationFactory $migrationFactory, SongDifficultyRepository $songDifficultyRepository, SongRepository $songRepository, SongService $songService)
    {
        $this->migrationFactory = $migrationFactory;
        $this->songDifficultyRepository = $songDifficultyRepository;
        $this->songRepository = $songRepository;
        $this->songService = $songService;
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof SongAwareMigrationInterface) {
            $instance->setSongDifficultyRepository($this->songDifficultyRepository);
            $instance->setSongRepository($this->songRepository);
            $instance->setSongService($this->songService);
        }

        return $instance;
    }
}