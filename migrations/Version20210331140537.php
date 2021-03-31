<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210331140537 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote ADD fun_factor DOUBLE PRECISION DEFAULT NULL, ADD rhythm DOUBLE PRECISION DEFAULT NULL, ADD flow DOUBLE PRECISION DEFAULT NULL, ADD pattern_quality DOUBLE PRECISION DEFAULT NULL, ADD readability DOUBLE PRECISION NOT NULL, ADD level_quality DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote DROP fun_factor, DROP rhythm, DROP flow, DROP pattern_quality, DROP readability, DROP level_quality');
    }
}
