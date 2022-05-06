<?php

namespace App\Enum;

enum EmailNotification
{
    /** general cases */
    case General_new_map;
    case General_stats_report;

    /** Follow */
    case Followed_mapper_new_map;
    case Followed_mapper_new_map_wip;
    case Followed_mapper_update_map;
    case Followed_mapper_update_map_wip;

    /** Mapper notifications */
    case Mapper_feedback_new;
    case Mapper_stats_report;

    public function label(): string
    {
        return match($this) {
            self::General_new_map => 'New map on RagnaCustoms',
            self::General_stats_report => 'Weekly stats from RagnaCustoms',
            self::Followed_mapper_new_map => 'New map from followed mapper(s)',
            self::Followed_mapper_new_map_wip => 'New "Work in progress" map from followed mapper(s)',
            self::Followed_mapper_update_map => 'Map update from followed mapper(s)',
            self::Followed_mapper_update_map_wip => '"Work in progress" map update from followed mapper(s)',
            self::Mapper_stats_report => 'Stats for your account (weekly downloads, followers, feedback, ...)',
            self::Mapper_feedback_new => "New feedback on your map",
        };
    }
}