<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230618142152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overlay CHANGE start_at start_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ranked_scores CHANGE total_ppscore total_ppscore DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE score CHANGE raw_pp raw_pp DOUBLE PRECISION DEFAULT NULL, CHANGE weighted_pp weighted_pp DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history CHANGE hit_accuracy hit_accuracy NUMERIC(20, 6) DEFAULT NULL, CHANGE raw_pp raw_pp DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE song CHANGE author_name author_name VARCHAR(255) DEFAULT NULL, CHANGE beats_per_minute beats_per_minute DOUBLE PRECISION DEFAULT NULL, CHANGE shuffle shuffle VARCHAR(255) DEFAULT NULL, CHANGE shuffle_period shuffle_period DOUBLE PRECISION DEFAULT NULL, CHANGE preview_start_time preview_start_time DOUBLE PRECISION DEFAULT NULL, CHANGE preview_duration preview_duration DOUBLE PRECISION DEFAULT NULL, CHANGE environment_name environment_name VARCHAR(255) DEFAULT NULL, CHANGE version version VARCHAR(255) DEFAULT NULL, CHANGE total_votes total_votes DOUBLE PRECISION DEFAULT NULL, CHANGE new_guid new_guid VARCHAR(255) DEFAULT NULL, CHANGE slug slug VARCHAR(255) DEFAULT NULL, CHANGE programmation_date programmation_date DATETIME DEFAULT NULL, CHANGE best_platform best_platform LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE song_difficulty CHANGE note_per_second note_per_second DOUBLE PRECISION DEFAULT NULL, CHANGE claw_difficulty claw_difficulty NUMERIC(20, 6) DEFAULT NULL, CHANGE wanadev_hash wanadev_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD auth_token VARCHAR(255) DEFAULT NULL, ADD auth_token_refresh VARCHAR(255) DEFAULT NULL, CHANGE steam_community_id steam_community_id VARCHAR(255) DEFAULT NULL, CHANGE mapper_name mapper_name VARCHAR(255) DEFAULT NULL, CHANGE mapper_img mapper_img VARCHAR(255) DEFAULT NULL, CHANGE mapper_discord mapper_discord VARCHAR(255) DEFAULT NULL, CHANGE api_key api_key VARCHAR(255) DEFAULT NULL, CHANGE discord_username discord_username VARCHAR(255) DEFAULT NULL, CHANGE discord_id discord_id VARCHAR(255) DEFAULT NULL, CHANGE discord_email discord_email VARCHAR(255) DEFAULT NULL, CHANGE patreon_access_token patreon_access_token VARCHAR(255) DEFAULT NULL, CHANGE patreon_refresh_token patreon_refresh_token VARCHAR(255) DEFAULT NULL, CHANGE patreon_user patreon_user VARCHAR(255) DEFAULT NULL, CHANGE username_color username_color VARCHAR(255) DEFAULT NULL, CHANGE twitch_access_token twitch_access_token VARCHAR(255) DEFAULT NULL, CHANGE twitch_refresh_token twitch_refresh_token VARCHAR(255) DEFAULT NULL, CHANGE twitch_user twitch_user VARCHAR(255) DEFAULT NULL, CHANGE last_api_attempt last_api_attempt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vote CHANGE fun_factor fun_factor DOUBLE PRECISION DEFAULT NULL, CHANGE rhythm rhythm DOUBLE PRECISION DEFAULT NULL, CHANGE flow flow DOUBLE PRECISION DEFAULT NULL, CHANGE pattern_quality pattern_quality DOUBLE PRECISION DEFAULT NULL, CHANGE readability readability DOUBLE PRECISION DEFAULT NULL, CHANGE level_quality level_quality DOUBLE PRECISION DEFAULT NULL, CHANGE hash hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overlay CHANGE start_at start_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE ranked_scores CHANGE total_ppscore total_ppscore DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE score CHANGE raw_pp raw_pp DOUBLE PRECISION DEFAULT \'NULL\', CHANGE weighted_pp weighted_pp DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE score_history CHANGE hit_accuracy hit_accuracy NUMERIC(20, 6) DEFAULT \'NULL\', CHANGE raw_pp raw_pp DOUBLE PRECISION DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE song CHANGE author_name author_name VARCHAR(255) DEFAULT \'NULL\', CHANGE beats_per_minute beats_per_minute DOUBLE PRECISION DEFAULT \'NULL\', CHANGE environment_name environment_name VARCHAR(255) DEFAULT \'NULL\', CHANGE new_guid new_guid VARCHAR(255) DEFAULT \'NULL\', CHANGE preview_duration preview_duration DOUBLE PRECISION DEFAULT \'NULL\', CHANGE preview_start_time preview_start_time DOUBLE PRECISION DEFAULT \'NULL\', CHANGE shuffle shuffle VARCHAR(255) DEFAULT \'NULL\', CHANGE shuffle_period shuffle_period DOUBLE PRECISION DEFAULT \'NULL\', CHANGE slug slug VARCHAR(255) DEFAULT \'NULL\', CHANGE total_votes total_votes DOUBLE PRECISION DEFAULT \'NULL\', CHANGE version version VARCHAR(255) DEFAULT \'NULL\', CHANGE programmation_date programmation_date DATETIME DEFAULT \'NULL\', CHANGE best_platform best_platform LONGTEXT DEFAULT \'NULL\' COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE song_difficulty CHANGE note_per_second note_per_second DOUBLE PRECISION DEFAULT \'NULL\', CHANGE claw_difficulty claw_difficulty NUMERIC(20, 6) DEFAULT \'NULL\', CHANGE wanadev_hash wanadev_hash VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE utilisateur DROP auth_token, DROP auth_token_refresh, CHANGE api_key api_key VARCHAR(255) DEFAULT \'NULL\', CHANGE discord_email discord_email VARCHAR(255) DEFAULT \'NULL\', CHANGE discord_id discord_id VARCHAR(255) DEFAULT \'NULL\', CHANGE discord_username discord_username VARCHAR(255) DEFAULT \'NULL\', CHANGE mapper_discord mapper_discord VARCHAR(255) DEFAULT \'NULL\', CHANGE mapper_img mapper_img VARCHAR(255) DEFAULT \'NULL\', CHANGE mapper_name mapper_name VARCHAR(255) DEFAULT \'NULL\', CHANGE username_color username_color VARCHAR(255) DEFAULT \'NULL\', CHANGE steam_community_id steam_community_id VARCHAR(255) DEFAULT \'NULL\', CHANGE patreon_access_token patreon_access_token VARCHAR(255) DEFAULT \'NULL\', CHANGE patreon_refresh_token patreon_refresh_token VARCHAR(255) DEFAULT \'NULL\', CHANGE patreon_user patreon_user VARCHAR(255) DEFAULT \'NULL\', CHANGE twitch_access_token twitch_access_token VARCHAR(255) DEFAULT \'NULL\', CHANGE twitch_refresh_token twitch_refresh_token VARCHAR(255) DEFAULT \'NULL\', CHANGE twitch_user twitch_user VARCHAR(255) DEFAULT \'NULL\', CHANGE last_api_attempt last_api_attempt DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE vote CHANGE flow flow DOUBLE PRECISION DEFAULT \'NULL\', CHANGE fun_factor fun_factor DOUBLE PRECISION DEFAULT \'NULL\', CHANGE level_quality level_quality DOUBLE PRECISION DEFAULT \'NULL\', CHANGE pattern_quality pattern_quality DOUBLE PRECISION DEFAULT \'NULL\', CHANGE readability readability DOUBLE PRECISION DEFAULT \'NULL\', CHANGE rhythm rhythm DOUBLE PRECISION DEFAULT \'NULL\', CHANGE hash hash VARCHAR(255) DEFAULT \'NULL\'');
    }
}
