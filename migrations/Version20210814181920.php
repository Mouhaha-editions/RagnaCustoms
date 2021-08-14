<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210814181920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD hash VARCHAR(255) NOT NULL, CHANGE song_hash difficulty VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD difficulty VARCHAR(255) NOT NULL, ADD hash VARCHAR(255) NOT NULL, DROP song_hash');
        $this->addSql('UPDATE score s
SET difficulty = (SELECT dr.level
                  FROM song_difficulty sd
                           LEFT JOIN difficulty_rank dr on sd.difficulty_rank_id = dr.id
                  WHERE sd.id = s.song_difficulty_id),
    s.hash = (SELECT s2.new_guid
              FROM song_difficulty sd
                       LEFT JOIN song s2 on s2.id = sd.song_id
              WHERE sd.id = s.song_difficulty_id) WHERE 1');

        $this->addSql('UPDATE score_history s
SET difficulty = (SELECT dr.level
                  FROM song_difficulty sd
                           LEFT JOIN difficulty_rank dr on sd.difficulty_rank_id = dr.id
                  WHERE sd.id = s.song_difficulty_id),
    s.hash = (SELECT s2.new_guid
              FROM song_difficulty sd
                       LEFT JOIN song s2 on s2.id = sd.song_id
              WHERE sd.id = s.song_difficulty_id) WHERE 1;');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP hash, CHANGE difficulty song_hash VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE score_history ADD song_hash VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP difficulty, DROP hash');
    }
}
