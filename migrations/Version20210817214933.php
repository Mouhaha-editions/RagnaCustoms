<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210817214933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback ADD hash VARCHAR(255) DEFAULT NULL, ADD difficulty VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE song_feedback s
SET difficulty = (SELECT dr.level
                  FROM song_difficulty sd
                           LEFT JOIN difficulty_rank dr on sd.difficulty_rank_id = dr.id
                  WHERE sd.id = s.song_difficulty_id),
    s.hash = (SELECT s2.new_guid
              FROM song s2
              WHERE s2.id = s.song_id) WHERE 1'); }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback DROP hash, DROP difficulty');
    }
}
