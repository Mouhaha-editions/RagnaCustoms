<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201104125426 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting ADD is_step_to_victory TINYINT(1) NOT NULL, ADD position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE run ADD last_visited_at DATETIME DEFAULT NULL, ADD comment LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge_setting DROP is_step_to_victory, DROP position');
        $this->addSql('ALTER TABLE run DROP last_visited_at, DROP comment');
    }
}
