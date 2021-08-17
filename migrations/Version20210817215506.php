<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210817215506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback DROP FOREIGN KEY FK_79F51210A0BDB2F3');
        $this->addSql('ALTER TABLE song_feedback DROP FOREIGN KEY FK_79F51210B37F772E');
        $this->addSql('DROP INDEX IDX_79F51210B37F772E ON song_feedback');
        $this->addSql('DROP INDEX IDX_79F51210A0BDB2F3 ON song_feedback');
        $this->addSql('ALTER TABLE song_feedback DROP song_id, DROP song_difficulty_id');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback ADD song_id INT NOT NULL, ADD song_difficulty_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210B37F772E FOREIGN KEY (song_difficulty_id) REFERENCES song_difficulty (id)');
        $this->addSql('CREATE INDEX IDX_79F51210B37F772E ON song_feedback (song_difficulty_id)');
        $this->addSql('CREATE INDEX IDX_79F51210A0BDB2F3 ON song_feedback (song_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
