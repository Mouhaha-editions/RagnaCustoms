<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230814084624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE custom_event (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, label VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type INT NOT NULL, banner LONGTEXT NOT NULL, max_challenger INT DEFAULT NULL, openning_date_registration DATETIME NOT NULL, closing_date_registration DATETIME NOT NULL, rules LONGTEXT DEFAULT NULL, enabled TINYINT(1) NOT NULL, edition VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F8A3F2CEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_event_participation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, custom_event_id INT DEFAULT NULL, current_score NUMERIC(20, 4) DEFAULT NULL, registration_validated TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_4BFEE3EBA76ED395 (user_id), INDEX IDX_4BFEE3EB94250FEF (custom_event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_event_score (id INT AUTO_INCREMENT NOT NULL, song_difficulty_id INT DEFAULT NULL, user_id INT NOT NULL, combo_blue INT DEFAULT NULL, combo_yellow INT DEFAULT NULL, country LONGTEXT DEFAULT NULL, date_ragnarock LONGTEXT DEFAULT NULL, extra LONGTEXT DEFAULT NULL, hit INT DEFAULT NULL, hit_delta_average INT DEFAULT NULL, hit_percentage INT DEFAULT NULL, missed INT DEFAULT NULL, percentage_of_perfects INT DEFAULT NULL, plateform VARCHAR(50) DEFAULT NULL, raw_pp DOUBLE PRECISION DEFAULT NULL, score DOUBLE PRECISION NOT NULL, session LONGTEXT DEFAULT NULL, user_ragnarock LONGTEXT DEFAULT NULL, weighted_pp DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_45BE33B7B37F772E (song_difficulty_id), INDEX IDX_45BE33B7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_event ADD CONSTRAINT FK_F8A3F2CEA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE custom_event_participation ADD CONSTRAINT FK_4BFEE3EBA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE custom_event_participation ADD CONSTRAINT FK_4BFEE3EB94250FEF FOREIGN KEY (custom_event_id) REFERENCES custom_event (id)');
        $this->addSql('ALTER TABLE custom_event_score ADD CONSTRAINT FK_45BE33B7B37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_event_score ADD CONSTRAINT FK_45BE33B7A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_event DROP FOREIGN KEY FK_F8A3F2CEA76ED395');
        $this->addSql('ALTER TABLE custom_event_participation DROP FOREIGN KEY FK_4BFEE3EBA76ED395');
        $this->addSql('ALTER TABLE custom_event_participation DROP FOREIGN KEY FK_4BFEE3EB94250FEF');
        $this->addSql('ALTER TABLE custom_event_score DROP FOREIGN KEY FK_45BE33B7B37F772E');
        $this->addSql('ALTER TABLE custom_event_score DROP FOREIGN KEY FK_45BE33B7A76ED395');
        $this->addSql('DROP TABLE custom_event');
        $this->addSql('DROP TABLE custom_event_participation');
        $this->addSql('DROP TABLE custom_event_score');
    }
}
