<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324190849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A0BDB2F3');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vote_counter DROP FOREIGN KEY FK_4A90CBE0A0BDB2F3');
        $this->addSql('ALTER TABLE vote_counter ADD CONSTRAINT FK_4A90CBE0A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_request CHANGE link link LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE author author VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_5A108564A0BDB2F3');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE vote_counter DROP FOREIGN KEY FK_4A90CBE0A0BDB2F3');
        $this->addSql('ALTER TABLE vote_counter ADD CONSTRAINT FK_4A90CBE0A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
    }
}
