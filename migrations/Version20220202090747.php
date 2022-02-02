<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220202090747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1F4B251C');
        $this->addSql('DROP INDEX IDX_33EDEEA1F4B251C ON song');
        $this->addSql('ALTER TABLE song DROP song_category_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song ADD song_category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1F4B251C FOREIGN KEY (song_category_id) REFERENCES song_category (id)');
        $this->addSql('CREATE INDEX IDX_33EDEEA1F4B251C ON song (song_category_id)');
    }
}
