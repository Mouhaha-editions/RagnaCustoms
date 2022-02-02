<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220202090314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
//         this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_song_category (song_id INT NOT NULL, song_category_id INT NOT NULL, INDEX IDX_E215A509A0BDB2F3 (song_id), INDEX IDX_E215A509F4B251C (song_category_id), PRIMARY KEY(song_id, song_category_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_song_category ADD CONSTRAINT FK_E215A509F4B251C FOREIGN KEY (song_category_id) REFERENCES song_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id)');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles JSON NOT NULL');
        $this->addSql('UPDATE vote SET is_anonymous = 0 WHERE is_anonymous IS NULL');
        $this->addSql('UPDATE vote SET is_moderated = 0 WHERE is_moderated IS NULL');
        $this->addSql('UPDATE vote SET is_public = 0 WHERE is_public IS NULL');
        $this->addSql('ALTER TABLE vote CHANGE is_anonymous is_anonymous TINYINT(1) NOT NULL, CHANGE is_moderated is_moderated TINYINT(1) NOT NULL, CHANGE is_public is_public TINYINT(1) NOT NULL');
        $this->addSql('INSERT INTO song_song_category (SELECT id, song_category_id FROM song WHERE song_category_id IS NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE song_song_category');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FF20744BAF');
        $this->addSql('ALTER TABLE song_difficulty DROP FOREIGN KEY FK_1C3F5FFA0BDB2F3');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FF20744BAF FOREIGN KEY (difficulty_rank_id) REFERENCES difficulty_rank (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_difficulty ADD CONSTRAINT FK_1C3F5FFA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_hash DROP FOREIGN KEY FK_A22BB44DA0BDB2F3');
        $this->addSql('ALTER TABLE song_hash ADD CONSTRAINT FK_A22BB44DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur CHANGE roles roles LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE vote CHANGE is_anonymous is_anonymous TINYINT(1) DEFAULT NULL, CHANGE is_moderated is_moderated TINYINT(1) DEFAULT NULL, CHANGE is_public is_public TINYINT(1) DEFAULT NULL');
    }
}
