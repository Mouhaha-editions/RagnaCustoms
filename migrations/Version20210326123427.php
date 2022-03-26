<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210326123427 extends AbstractMigration
{
    public function getDescription() : string
    {

//        UPDATE score SET raw_PP = FLOOR(RAND()*(10000+1))/100;
//INSERT INTO ranked_scores (SELECT null,s.user_id,(FLOOR(RAND()*(25000-1000+1))+1000)/100, NOW(),NOW() FROM score s GROUP BY s.user_id);
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE difficulty_rank (id INT AUTO_INCREMENT NOT NULL, level INT NOT NULL, color VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, sub_name VARCHAR(255) NOT NULL, author_name VARCHAR(255) NOT NULL, level_author_name VARCHAR(255) NOT NULL, beats_per_minute DOUBLE PRECISION NOT NULL, shuffle VARCHAR(255) NOT NULL, shuffle_period DOUBLE PRECISION NOT NULL, preview_start_time DOUBLE PRECISION NOT NULL, preview_duration DOUBLE PRECISION NOT NULL, approximative_duration INT NOT NULL, file_name VARCHAR(255) NOT NULL, cover_image_file_name VARCHAR(255) NOT NULL, environment_name VARCHAR(255) NOT NULL, time_offset INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_difficulty (id INT AUTO_INCREMENT NOT NULL, difficulty_rank_id INT DEFAULT NULL, song_id INT DEFAULT NULL, difficulty VARCHAR(255) NOT NULL, note_jump_movement_speed INT NOT NULL, note_jump_start_beat_offset INT NOT NULL, INDEX IDX_1C3F5FF20744BAF (difficulty_rank_id), INDEX IDX_1C3F5FFA0BDB2F3 (song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id)');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');

        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (1, 1, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (2, 2, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (3, 3, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (4, 4, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (5, 5, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (6, 6, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (7, 7, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (8, 8, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (9, 9, \'00000\');');
        $this->addSql('INSERT INTO `difficulty_rank` (`id`, `level`, `color`) VALUES (10, 10, \'00000\');');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('DROP TABLE difficulty_rank');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE song_difficulty');
        $this->addSql('DROP TABLE utilisateur');
    }
}
