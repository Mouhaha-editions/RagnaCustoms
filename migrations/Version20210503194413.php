<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210503194413 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_feedback (id INT AUTO_INCREMENT NOT NULL, song_id INT NOT NULL, song_difficulty_id INT DEFAULT NULL, is_public TINYINT(1) NOT NULL, is_anonymous TINYINT(1) NOT NULL, feedback LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_79F51210A0BDB2F3 (song_id), INDEX IDX_79F51210B37F772E (song_difficulty_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210B37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE song_feedback');
    }
}
