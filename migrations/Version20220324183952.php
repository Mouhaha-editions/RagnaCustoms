<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324183952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751A0BDB2F3');
        $this->addSql('DROP INDEX IDX_32993751A0BDB2F3 ON score');
        $this->addSql('ALTER TABLE score ADD song VARCHAR(255) DEFAULT NULL, DROP song_id');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD song_id INT DEFAULT NULL, DROP song');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('CREATE INDEX IDX_32993751A0BDB2F3 ON score (song_id)');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_request CHANGE link link LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE author author VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
