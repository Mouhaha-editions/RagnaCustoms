<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220401153008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_temporary_list (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_temporary_list_song (song_temporary_list_id INT NOT NULL, song_id INT NOT NULL, INDEX IDX_70CEB528D27F4E34 (song_temporary_list_id), INDEX IDX_70CEB528A0BDB2F3 (song_id), PRIMARY KEY(song_temporary_list_id, song_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_temporary_list_song ADD CONSTRAINT FK_70CEB528D27F4E34 FOREIGN KEY (song_temporary_list_id) REFERENCES song_temporary_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_temporary_list_song ADD CONSTRAINT FK_70CEB528A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_temporary_list_song DROP FOREIGN KEY FK_70CEB528D27F4E34');
        $this->addSql('DROP TABLE song_temporary_list');
        $this->addSql('DROP TABLE song_temporary_list_song');
    }
}
