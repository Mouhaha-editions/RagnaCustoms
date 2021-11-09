<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211109224336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_request (id INT AUTO_INCREMENT NOT NULL, requested_by_id INT NOT NULL, link LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_36FC9EB04DA1E751 (requested_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_request_utilisateur (song_request_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_EB7587B4F8D622B5 (song_request_id), INDEX IDX_EB7587B4FB88E14F (utilisateur_id), PRIMARY KEY(song_request_id, utilisateur_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_request ADD CONSTRAINT FK_36FC9EB04DA1E751 FOREIGN KEY (requested_by_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE song_request_utilisateur ADD CONSTRAINT FK_EB7587B4F8D622B5 FOREIGN KEY (song_request_id) REFERENCES song_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_request_utilisateur ADD CONSTRAINT FK_EB7587B4FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id)');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_request_utilisateur DROP FOREIGN KEY FK_EB7587B4F8D622B5');
        $this->addSql('DROP TABLE song_request');
        $this->addSql('DROP TABLE song_request_utilisateur');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
