<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325084608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFA0BDB2F3');
        $this->addSql('DROP INDEX IDX_463255DFA0BDB2F3 ON score_history');
        $this->addSql('ALTER TABLE score_history ADD song VARCHAR(255) DEFAULT NULL, DROP song_id, CHANGE difficulty difficulty VARCHAR(255) DEFAULT NULL, CHANGE hash hash VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score_history ADD song_id INT DEFAULT NULL, DROP song, CHANGE difficulty difficulty VARCHAR(255) NOT NULL, CHANGE hash hash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('CREATE INDEX IDX_463255DFA0BDB2F3 ON score_history (song_id)');
    }
}
