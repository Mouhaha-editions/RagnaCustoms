<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325142159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD user_ragnarock LONGTEXT DEFAULT NULL, ADD country LONGTEXT DEFAULT NULL, ADD plateform LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD user_ragnarock LONGTEXT DEFAULT NULL, ADD country LONGTEXT DEFAULT NULL, ADD plateform LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP user_ragnarock, DROP country, DROP plateform');
        $this->addSql('ALTER TABLE score_history DROP user_ragnarock, DROP country, DROP plateform');
    }
}
