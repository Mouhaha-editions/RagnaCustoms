<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210909204704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_date DROP FOREIGN KEY FK_A33130FC98A21AC6');
        $this->addSql('ALTER TABLE challenge_newsletter DROP FOREIGN KEY FK_1E959CC198A21AC6');
        $this->addSql('DROP TABLE block_cms');
        $this->addSql('DROP TABLE challenge');
        $this->addSql('DROP TABLE challenge_date');
        $this->addSql('DROP TABLE challenge_newsletter');
        $this->addSql('ALTER TABLE season ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE block_cms (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type INT NOT NULL, date_created DATETIME NOT NULL, date_updated DATETIME NOT NULL, UNIQUE INDEX UNIQ_CE04503D989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE challenge (id INT AUTO_INCREMENT NOT NULL, season_id INT DEFAULT NULL, user_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, type INT NOT NULL, banner LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, max_challenger INT NOT NULL, registration_opening DATETIME NOT NULL, registration_closing DATETIME NOT NULL, malus_per_run NUMERIC(10, 2) NOT NULL, malus_max NUMERIC(10, 2) NOT NULL, display TINYINT(1) DEFAULT NULL, display_total_in_mod TINYINT(1) DEFAULT NULL, display_rules_and_ratios_before_start TINYINT(1) DEFAULT NULL, the_file VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_D7098951A76ED395 (user_id), INDEX IDX_D70989514EC001D1 (season_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE challenge_date (id INT AUTO_INCREMENT NOT NULL, challenge_id INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, INDEX IDX_A33130FC98A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE challenge_newsletter (id INT AUTO_INCREMENT NOT NULL, challenge_id INT NOT NULL, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_1E959CC198A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE challenge ADD CONSTRAINT FK_D70989514EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE challenge ADD CONSTRAINT FK_D7098951A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE challenge_date ADD CONSTRAINT FK_A33130FC98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE challenge_newsletter ADD CONSTRAINT FK_1E959CC198A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('ALTER TABLE season DROP slug');
        $this->addSql('ALTER TABLE song DROP slug');
    }
}
