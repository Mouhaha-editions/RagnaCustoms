<?php


namespace App\Enum;


class EGamification
{
    const ACHIEVEMENT_DISTANCE_1 = 1;
    const ACHIEVEMENT_DISTANCE_2 = 2;
    const ACHIEVEMENT_DISTANCE_3 = 3;
    const ACHIEVEMENT_DISTANCE_4 = 4;

    const ACHIEVEMENT_HELPER_LVL_1 = 5;
    const ACHIEVEMENT_HELPER_LVL_2 = 6;
    const ACHIEVEMENT_HELPER_LVL_3 = 7;

    const ACHIEVEMENT_GLOBAL_RANKING_POINTS_1 = 8;
    const ACHIEVEMENT_GLOBAL_RANKING_POINTS_2 = 9;
    const ACHIEVEMENT_GLOBAL_RANKING_POINTS_3 = 10;
    const ACHIEVEMENT_USE_API = 11;

//    const ACHIEVEMENT_SEASON_RANKING_TOP_1 = 11;
//    const ACHIEVEMENT_SEASON_RANKING_TOP_5 = 12;

const ICONS = [
     self::ACHIEVEMENT_USE_API => "fas fa-key  text-info",
     self::ACHIEVEMENT_DISTANCE_1 => "fas fa-route text-info",
     self::ACHIEVEMENT_DISTANCE_2 => "fas fa-route text-warning",
     self::ACHIEVEMENT_DISTANCE_3 => "fas fa-route text-danger",
     self::ACHIEVEMENT_DISTANCE_4 => "fas fa-route text-success",

     self::ACHIEVEMENT_HELPER_LVL_1 =>"fas fa-hands-helping text-info",
     self::ACHIEVEMENT_HELPER_LVL_2 =>"fas fa-hands-helping text-warning",
     self::ACHIEVEMENT_HELPER_LVL_3 =>"fas fa-hands-helping text-danger",

     self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_1 => "fas fa-trophy text-info",
     self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_2 => "fas fa-trophy text-warning",
     self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_3 => "fas fa-trophy text-danger",

//     self::ACHIEVEMENT_SEASON_RANKING_TOP_1 => 11,
//     self::ACHIEVEMENT_SEASON_RANKING_TOP_5 => 12,
];
    const TEXTS = [
        self::ACHIEVEMENT_USE_API => "Use your API key at least one time",

        self::ACHIEVEMENT_DISTANCE_1 => "Travel",
        self::ACHIEVEMENT_DISTANCE_2 => "Travel a lot",
        self::ACHIEVEMENT_DISTANCE_3 => "Travel more",
        self::ACHIEVEMENT_DISTANCE_4 => "Make the travel of your life",

        self::ACHIEVEMENT_HELPER_LVL_1 => "Help mapper on least one time",
        self::ACHIEVEMENT_HELPER_LVL_2 => "Help mappers 10 times",
        self::ACHIEVEMENT_HELPER_LVL_3 => "Help mappers 50 times",

        self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_1 => "Get 1000 points in global ranking",
        self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_2 => "Get 5 000 points in global ranking",
        self::ACHIEVEMENT_GLOBAL_RANKING_POINTS_3 => "Get 10 000 points in global ranking",

//        self::ACHIEVEMENT_SEASON_RANKING_TOP_1 => "Be in top 1 at season ranking after the 7th day of the season",
//        self::ACHIEVEMENT_SEASON_RANKING_TOP_5 => "Be in top 5 at season ranking after the 7th day of the season",

    ];
    const HIDDEN = [
//        self::ACHIEVEMENT_DISTANCE_1 => "Travel",
        self::ACHIEVEMENT_DISTANCE_2 => "Travel a lot",
        self::ACHIEVEMENT_DISTANCE_3 => "Travel more",
        self::ACHIEVEMENT_DISTANCE_4 => "Make the travel of your life",
//        self::ACHIEVEMENT_HELPER_LVL_1 => "Help mapper on least one time",
//        self::ACHIEVEMENT_HELPER_LVL_2 => "Help mappers 10 times",
//        self::ACHIEVEMENT_HELPER_LVL_3 => "help mappers 50 times",

    ];

    const TITLES = [
        self::ACHIEVEMENT_USE_API => "Api user .. need to find better soon",

    ];

}