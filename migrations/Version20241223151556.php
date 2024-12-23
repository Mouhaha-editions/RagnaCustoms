<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241223151556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE changelog (id INT AUTO_INCREMENT NOT NULL, base_description LONGTEXT DEFAULT NULL, premium_description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE changelog_utilisateur (changelog_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_2F7DAE7B3C8F0C57 (changelog_id), INDEX IDX_2F7DAE7BFB88E14F (utilisateur_id), PRIMARY KEY(changelog_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE changelog_utilisateur ADD CONSTRAINT FK_2F7DAE7B3C8F0C57 FOREIGN KEY (changelog_id) REFERENCES changelog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE changelog_utilisateur ADD CONSTRAINT FK_2F7DAE7BFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE changelog_utilisateur DROP FOREIGN KEY FK_2F7DAE7B3C8F0C57');
        $this->addSql('ALTER TABLE changelog_utilisateur DROP FOREIGN KEY FK_2F7DAE7BFB88E14F');
        $this->addSql('DROP TABLE changelog');
        $this->addSql('DROP TABLE changelog_utilisateur');
    }
}
