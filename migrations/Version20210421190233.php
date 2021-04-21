<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210421190233 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE score (id INT AUTO_INCREMENT NOT NULL, song_id INT NOT NULL, user_id INT NOT NULL, score DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_32993751A0BDB2F3 (song_id), INDEX IDX_32993751A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE utilisateur ADD is_mapper TINYINT(1) DEFAULT NULL, ADD mapper_name VARCHAR(255) NOT NULL, ADD mapper_description VARCHAR(255) DEFAULT NULL, ADD mapper_img VARCHAR(255) DEFAULT NULL, ADD mailing_new_song TINYINT(1) NOT NULL, ADD mapper_discord VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE score');
        $this->addSql('ALTER TABLE utilisateur DROP is_mapper, DROP mapper_name, DROP mapper_description, DROP mapper_img, DROP mailing_new_song, DROP mapper_discord');
    }
}
