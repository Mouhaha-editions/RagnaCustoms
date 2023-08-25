<?php

namespace App\Enum;

enum ENotification: int
{
    /** general cases */
    case General_new_map = 1;

    /** Follow */
    case Followed_mapper_new_map = 50;
    case Followed_mapper_new_map_wip = 51;
    case Followed_mapper_update_map = 52;
    case Followed_mapper_update_map_wip = 53;

    /** Mapper notifications */
    case Mapper_new_up_down_vote = 100;
    case Mapper_new_feedback = 101;
    case Mapper_new_follower = 102;

    public function label(): string
    {
        return match ($this) {
            self::General_new_map => 'New map on RagnaCustoms (soon)',
//            self::General_stats_report => 'Weekly stats from RagnaCustoms',
            self::Followed_mapper_new_map => 'New map from followed mapper(s)',
            self::Followed_mapper_new_map_wip => 'New "Work in progress" map from followed mapper(s)',
            self::Followed_mapper_update_map => 'Map update from followed mapper(s)',
            self::Followed_mapper_update_map_wip => '"Work in progress" map update from followed mapper(s)',
//            self::Mapper_stats_report => 'Stats for your account (weekly downloads, followers, feedback, ...)',
            self::Mapper_new_feedback => "New feedback on your map (soon)",
            self::Mapper_new_up_down_vote => "New up/down vote on your map (soon)",
            self::Mapper_new_follower => "New follower (soon)",
        };
    }
}