<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241224093035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE changelog ADD description LONGTEXT DEFAULT NULL, DROP base_description, DROP premium_description, DROP base_title, DROP premium_title');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE changelog ADD premium_description LONGTEXT DEFAULT NULL, ADD base_title VARCHAR(255) DEFAULT NULL, ADD premium_title VARCHAR(255) DEFAULT NULL, CHANGE description base_description LONGTEXT DEFAULT NULL');
    }
}
