<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201102081236 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE run (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, challenge_id INT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, INDEX IDX_5076A4C0A76ED395 (user_id), INDEX IDX_5076A4C098A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE run_settings (id INT AUTO_INCREMENT NOT NULL, run_id INT DEFAULT NULL, challenge_setting_id INT NOT NULL, value NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_DE73E27D84E3FEC4 (run_id), INDEX IDX_DE73E27D8E25A47B (challenge_setting_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE run ADD CONSTRAINT FK_5076A4C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE run ADD CONSTRAINT FK_5076A4C098A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D84E3FEC4 FOREIGN KEY (run_id) REFERENCES run (id)');
        $this->addSql('ALTER TABLE run_settings ADD CONSTRAINT FK_DE73E27D8E25A47B FOREIGN KEY (challenge_setting_id) REFERENCES challenge_setting (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE run_settings DROP FOREIGN KEY FK_DE73E27D84E3FEC4');
        $this->addSql('DROP TABLE run');
        $this->addSql('DROP TABLE run_settings');
    }
}
