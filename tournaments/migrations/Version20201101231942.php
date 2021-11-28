<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201101231942 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE challenge_setting (id INT AUTO_INCREMENT NOT NULL, challenge_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, ratio NUMERIC(10, 0) DEFAULT NULL, input_type INT NOT NULL, default_value NUMERIC(10, 0) NOT NULL, is_used_for_score TINYINT(1) NOT NULL, INDEX IDX_73E4F71D98A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE challenge_setting ADD CONSTRAINT FK_73E4F71D98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE challenge_setting');
    }
}
