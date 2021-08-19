<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210819222604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD notes_hit INT DEFAULT NULL, ADD notes_missed INT DEFAULT NULL, ADD notes_not_processed INT DEFAULT NULL, ADD hit_accuracy NUMERIC(10, 2) DEFAULT NULL, ADD percentage NUMERIC(10, 2) DEFAULT NULL, ADD percentage2 NUMERIC(10, 2) DEFAULT NULL, ADD hit_speed NUMERIC(10, 2) DEFAULT NULL, ADD combos INT DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD notes_hit INT DEFAULT NULL, ADD notes_missed INT DEFAULT NULL, ADD notes_not_processed INT DEFAULT NULL, ADD hit_accuracy NUMERIC(10, 2) DEFAULT NULL, ADD percentage NUMERIC(10, 2) DEFAULT NULL, ADD percentage2 NUMERIC(10, 2) DEFAULT NULL, ADD hit_speed NUMERIC(10, 2) DEFAULT NULL, ADD combos INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP notes_hit, DROP notes_missed, DROP notes_not_processed, DROP hit_accuracy, DROP percentage, DROP percentage2, DROP hit_speed, DROP combos');
        $this->addSql('ALTER TABLE score_history DROP notes_hit, DROP notes_missed, DROP notes_not_processed, DROP hit_accuracy, DROP percentage, DROP percentage2, DROP hit_speed, DROP combos');
    }
}
