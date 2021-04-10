<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210405225518 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE download_counter ADD created_at DATETIME NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE utilisateur ADD created_at DATETIME NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE view_counter ADD created_at DATETIME NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE download_counter DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE utilisateur DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE view_counter DROP created_at, DROP updated_at');
    }
}
