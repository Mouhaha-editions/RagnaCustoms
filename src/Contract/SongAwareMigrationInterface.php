<?php

namespace App\Contract;

use App\Repository\SongDifficultyRepository;
use App\Repository\SongRepository;
use App\Service\SongService;

interface SongAwareMigrationInterface
{
    public function setSongDifficultyRepository(SongDifficultyRepository $songDifficultyRepository): void;
    public function setSongRepository(SongRepository $songRepository): void;
    public function setSongService(SongService $songService): void;
}