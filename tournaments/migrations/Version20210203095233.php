<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210203095233 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting CHANGE auto_value auto_value VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE run_settings DROP FOREIGN KEY FK_DE73E27D84E3FEC4');
        $this->addSql('ALTER TABLE run_settings DROP FOREIGN KEY FK_DE73E27D8E25A47B');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D84E3FEC4 FOREIGN KEY (run_id) REFERENCES run (id)');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D8E25A47B FOREIGN KEY (challenge_setting_id) REFERENCES challenge_setting (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting CHANGE auto_value auto_value VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE run_settings DROP FOREIGN KEY FK_DE73E27D84E3FEC4');
        $this->addSql('ALTER TABLE run_settings DROP FOREIGN KEY FK_DE73E27D8E25A47B');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D84E3FEC4 FOREIGN KEY (run_id) REFERENCES run (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D8E25A47B FOREIGN KEY (challenge_setting_id) REFERENCES challenge_setting (id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
