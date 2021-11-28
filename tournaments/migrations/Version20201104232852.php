<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201104232852 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting ADD step_to_victory_min NUMERIC(10, 2) DEFAULT NULL, ADD step_to_victory_max NUMERIC(10, 2) DEFAULT NULL, ADD display_for_stats TINYINT(1) NOT NULL, ADD display_best_for_stats TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting DROP step_to_victory_min, DROP step_to_victory_max, DROP display_for_stats, DROP display_best_for_stats');
    }
}
