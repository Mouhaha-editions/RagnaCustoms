<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324190310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD combo_blue INT DEFAULT NULL, ADD combo_yellow INT DEFAULT NULL, ADD hit INT DEFAULT NULL, ADD hit_delta_average INT DEFAULT NULL, ADD hit_percentage INT DEFAULT NULL, ADD missed INT DEFAULT NULL, ADD percentage_of_perfects INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP combo_blue, DROP combo_yellow, DROP hit, DROP hit_delta_average, DROP hit_percentage, DROP missed, DROP percentage_of_perfects');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_request CHANGE link link LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE title title VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE author author VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
