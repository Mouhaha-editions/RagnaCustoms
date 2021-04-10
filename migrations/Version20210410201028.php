<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410201028 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE download_counter DROP FOREIGN KEY FK_6D5C95BDA0BDB2F3');
        $this->addSql('ALTER TABLE download_counter DROP FOREIGN KEY FK_6D5C95BDA76ED395');
        $this->addSql('ALTER TABLE download_counter ADD CONSTRAINT FK_6D5C95BDA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE download_counter ADD CONSTRAINT FK_6D5C95BDA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE view_counter DROP FOREIGN KEY FK_E87F8182A0BDB2F3');
        $this->addSql('ALTER TABLE view_counter DROP FOREIGN KEY FK_E87F8182A76ED395');
        $this->addSql('ALTER TABLE view_counter CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE view_counter ADD CONSTRAINT FK_E87F8182A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE view_counter ADD CONSTRAINT FK_E87F8182A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE download_counter DROP FOREIGN KEY FK_6D5C95BDA0BDB2F3');
        $this->addSql('ALTER TABLE download_counter DROP FOREIGN KEY FK_6D5C95BDA76ED395');
        $this->addSql('ALTER TABLE download_counter ADD CONSTRAINT FK_6D5C95BDA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE download_counter ADD CONSTRAINT FK_6D5C95BDA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE view_counter DROP FOREIGN KEY FK_E87F8182A0BDB2F3');
        $this->addSql('ALTER TABLE view_counter DROP FOREIGN KEY FK_E87F8182A76ED395');
        $this->addSql('ALTER TABLE view_counter CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE view_counter ADD CONSTRAINT FK_E87F8182A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE view_counter ADD CONSTRAINT FK_E87F8182A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
    }
}
