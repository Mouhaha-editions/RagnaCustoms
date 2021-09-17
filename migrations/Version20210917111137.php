<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210917111137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE playlist (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, label VARCHAR(255) NOT NULL, is_public TINYINT(1) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_D782112DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_song (playlist_id INT NOT NULL, song_id INT NOT NULL, INDEX IDX_93F4D9C36BBD148 (playlist_id), INDEX IDX_93F4D9C3A0BDB2F3 (song_id), PRIMARY KEY(playlist_id, song_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE playlist ADD CONSTRAINT FK_D782112DA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE playlist_song ADD CONSTRAINT FK_93F4D9C36BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE playlist_song ADD CONSTRAINT FK_93F4D9C3A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_329937514EC001D1');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751A76ED395');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_329937514EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFA76ED395');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id)');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE playlist_song DROP FOREIGN KEY FK_93F4D9C36BBD148');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_song');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_32993751A76ED395');
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_329937514EC001D1');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_32993751A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_329937514EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE score_history DROP FOREIGN KEY FK_463255DFA76ED395');
        $this->addSql('ALTER TABLE score_history ADD CONSTRAINT FK_463255DFA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
    }
}
