<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210814170256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_hash (id INT AUTO_INCREMENT NOT NULL, song_id INT DEFAULT NULL, version INT NOT NULL, hash VARCHAR(255) NOT NULL, INDEX IDX_A22BB44DA0BDB2F3 (song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE score ADD song_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD song_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD active TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE song_hash');
        $this->addSql('ALTER TABLE score DROP song_hash');
        $this->addSql('ALTER TABLE score_history DROP song_hash');
        $this->addSql('ALTER TABLE song DROP active');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
