<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210814184425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751B37F772E');
        $this->addSql('DROP INDEX IDX_32993751B37F772E ON score');
        $this->addSql('DROP INDEX user_difficulty ON score');
        $this->addSql('ALTER TABLE score DROP song_difficulty_id');
        $this->addSql('CREATE UNIQUE INDEX user_difficulty ON score (user_id, season_id, hash, difficulty)');
        $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFB37F772E');
        $this->addSql('DROP INDEX IDX_463255DFB37F772E ON score_history');
        $this->addSql('ALTER TABLE score_history DROP song_difficulty_id');
        $this->addSql('INSERT INTO song_hash  (SELECT null,id,1,new_guid FROM song )');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX user_difficulty ON score');
        $this->addSql('ALTER TABLE score ADD song_difficulty_id INT NOT NULL');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751B37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id)');
        $this->addSql('CREATE INDEX IDX_32993751B37F772E ON score (song_difficulty_id)');
        $this->addSql('CREATE UNIQUE INDEX user_difficulty ON score (user_id, song_difficulty_id, season_id)');
        $this->addSql('ALTER TABLE score_history ADD song_difficulty_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFB37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id)');
        $this->addSql('CREATE INDEX IDX_463255DFB37F772E ON score_history (song_difficulty_id)');
    }
}
