<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230902075440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_utilisateur (song_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_11DB4DA0BDB2F3 (song_id), INDEX IDX_11DB4DFB88E14F (utilisateur_id), PRIMARY KEY(song_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_utilisateur ADD CONSTRAINT FK_11DB4DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_utilisateur ADD CONSTRAINT FK_11DB4DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO song_utilisateur (SELECT song.id,song.user_id FROM song); ');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_utilisateur DROP FOREIGN KEY FK_11DB4DA0BDB2F3');
        $this->addSql('ALTER TABLE song_utilisateur DROP FOREIGN KEY FK_11DB4DFB88E14F');
        $this->addSql('DROP TABLE song_utilisateur');
    }
}
