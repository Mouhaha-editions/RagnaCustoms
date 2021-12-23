<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211223210056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback ADD song_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('UPDATE song_feedback set song_id = (SELECT song_id FROM song_hash WHERE song_hash.hash = song_feedback.hash)');
        $this->addSql('CREATE INDEX IDX_79F51210A0BDB2F3 ON song_feedback (song_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback DROP FOREIGN KEY FK_79F51210A0BDB2F3');
        $this->addSql('DROP INDEX IDX_79F51210A0BDB2F3 ON song_feedback');
        $this->addSql('ALTER TABLE song_feedback DROP song_id');
    }
}
