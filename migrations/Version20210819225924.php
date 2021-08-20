<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210819225924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score CHANGE hit_accuracy hit_accuracy NUMERIC(20, 6) DEFAULT NULL, CHANGE percentage percentage NUMERIC(20, 6) DEFAULT NULL, CHANGE percentage2 percentage2 NUMERIC(20, 6) DEFAULT NULL, CHANGE hit_speed hit_speed NUMERIC(20, 6) DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history CHANGE hit_accuracy hit_accuracy NUMERIC(20, 6) DEFAULT NULL, CHANGE percentage percentage NUMERIC(20, 6) DEFAULT NULL, CHANGE percentage2 percentage2 NUMERIC(20, 6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score CHANGE hit_accuracy hit_accuracy NUMERIC(10, 2) DEFAULT NULL, CHANGE percentage percentage NUMERIC(10, 2) DEFAULT NULL, CHANGE percentage2 percentage2 NUMERIC(10, 2) DEFAULT NULL, CHANGE hit_speed hit_speed NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history CHANGE hit_accuracy hit_accuracy NUMERIC(10, 2) DEFAULT NULL, CHANGE percentage percentage NUMERIC(10, 2) DEFAULT NULL, CHANGE percentage2 percentage2 NUMERIC(10, 2) DEFAULT NULL');
    }
}
