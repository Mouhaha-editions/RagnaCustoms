<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211016103531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
    }
}
