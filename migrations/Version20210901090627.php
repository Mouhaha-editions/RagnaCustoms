<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210901090627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE song_song_category');
        $this->addSql('ALTER TABLE song ADD song_category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1F4B251C FOREIGN KEY (song_category_id) REFERENCES song_category (id)');
        $this->addSql('CREATE INDEX IDX_33EDEEA1F4B251C ON song (song_category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_song_category (song_id INT NOT NULL, song_category_id INT NOT NULL, INDEX IDX_E215A509A0BDB2F3 (song_id), INDEX IDX_E215A509F4B251C (song_category_id), PRIMARY KEY(song_id, song_category_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509F4B251C FOREIGN KEY (song_category_id) REFERENCES song_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1F4B251C');
        $this->addSql('DROP INDEX IDX_33EDEEA1F4B251C ON song');
        $this->addSql('ALTER TABLE song DROP song_category_id');
    }
}
