<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210901082647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_song_category (song_id INT NOT NULL, song_category_id INT NOT NULL, INDEX IDX_E215A509A0BDB2F3 (song_id), INDEX IDX_E215A509F4B251C (song_category_id), PRIMARY KEY(song_id, song_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_category (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, is_feedbackable TINYINT(1) NOT NULL, is_reviewable TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509F4B251C FOREIGN KEY (song_category_id) REFERENCES song_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song ADD is_deleted TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_song_category DROP FOREIGN KEY FK_E215A509F4B251C');
        $this->addSql('DROP TABLE song_song_category');
        $this->addSql('DROP TABLE song_category');
        $this->addSql('ALTER TABLE song DROP is_deleted');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
