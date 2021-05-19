<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210518150737 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_79F51210A76ED395 ON song_feedback (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback DROP FOREIGN KEY FK_79F51210A76ED395');
        $this->addSql('DROP INDEX IDX_79F51210A76ED395 ON song_feedback');
        $this->addSql('ALTER TABLE song_feedback DROP user_id');
    }
}
