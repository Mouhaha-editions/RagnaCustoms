<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210811142833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE overlay (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, difficulty_id INT DEFAULT NULL, disposition LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_B9FF3CBEA76ED395 (user_id), UNIQUE INDEX UNIQ_B9FF3CBEFCFA9DAE (difficulty_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE score_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, song_difficulty_id INT DEFAULT NULL, score DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_463255DFA76ED395 (user_id), INDEX IDX_463255DFB37F772E (song_difficulty_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE overlay ADD CONSTRAINT FK_B9FF3CBEA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE overlay ADD CONSTRAINT FK_B9FF3CBEFCFA9DAE FOREIGN KEY (difficulty_id) REFERENCES song_difficulty (id)');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFB37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE overlay');
        $this->addSql('DROP TABLE score_history');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
