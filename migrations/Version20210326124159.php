<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210326124159 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song ADD version VARCHAR(255) DEFAULT NULL, CHANGE author_name author_name VARCHAR(255) DEFAULT NULL, CHANGE beats_per_minute beats_per_minute DOUBLE PRECISION DEFAULT NULL, CHANGE shuffle shuffle VARCHAR(255) DEFAULT NULL, CHANGE shuffle_period shuffle_period DOUBLE PRECISION DEFAULT NULL, CHANGE preview_start_time preview_start_time DOUBLE PRECISION DEFAULT NULL, CHANGE preview_duration preview_duration DOUBLE PRECISION DEFAULT NULL, CHANGE approximative_duration approximative_duration INT DEFAULT NULL, CHANGE environment_name environment_name VARCHAR(255) DEFAULT NULL, CHANGE time_offset time_offset INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song DROP version, CHANGE author_name author_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE beats_per_minute beats_per_minute DOUBLE PRECISION NOT NULL, CHANGE shuffle shuffle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE shuffle_period shuffle_period DOUBLE PRECISION NOT NULL, CHANGE preview_start_time preview_start_time DOUBLE PRECISION NOT NULL, CHANGE preview_duration preview_duration DOUBLE PRECISION NOT NULL, CHANGE approximative_duration approximative_duration INT NOT NULL, CHANGE environment_name environment_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE time_offset time_offset INT NOT NULL');
    }
}
