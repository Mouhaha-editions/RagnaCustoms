<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220419214628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_difficulty ADD theorical_min_score DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX UNIQ_1D1C63B34A3132F ON utilisateur');
        $this->addSql('DROP INDEX UNIQ_1D1C63B343349DE ON utilisateur');
        $this->addSql('DROP INDEX UNIQ_1D1C63B3E7EBAA77 ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP discord_username, DROP discord_id, DROP discord_email');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_difficulty DROP theorical_min_score');
        $this->addSql('ALTER TABLE utilisateur ADD discord_username VARCHAR(255) DEFAULT NULL, ADD discord_id VARCHAR(255) DEFAULT NULL, ADD discord_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B34A3132F ON utilisateur (discord_username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B343349DE ON utilisateur (discord_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7EBAA77 ON utilisateur (discord_email)');
    }
}
