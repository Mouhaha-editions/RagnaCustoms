<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220402211857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP FOREIGN KEY FK_329937514EC001D1');
        $this->addSql('DROP INDEX IDX_329937514EC001D1 ON score');
        $this->addSql('DROP INDEX user_difficulty ON score');
        $this->addSql('ALTER TABLE score DROP season_id, DROP difficulty, DROP hash, DROP notes_hit, DROP notes_missed, DROP notes_not_processed, DROP hit_accuracy, DROP percentage, DROP percentage2, DROP hit_speed, DROP combos, DROP song');
        $this->addSql('CREATE UNIQUE INDEX user_difficulty_2 ON score (user_id, song_difficulty_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX user_difficulty_2 ON score');
        $this->addSql('ALTER TABLE score ADD season_id INT DEFAULT NULL, ADD difficulty VARCHAR(255) DEFAULT NULL, ADD hash VARCHAR(255) DEFAULT NULL, ADD notes_hit INT DEFAULT NULL, ADD notes_missed INT DEFAULT NULL, ADD notes_not_processed INT DEFAULT NULL, ADD hit_accuracy NUMERIC(20, 6) DEFAULT NULL, ADD percentage NUMERIC(20, 6) DEFAULT NULL, ADD percentage2 NUMERIC(20, 6) DEFAULT NULL, ADD hit_speed NUMERIC(20, 6) DEFAULT NULL, ADD combos INT DEFAULT NULL, ADD song VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE score ADD CONSTRAINT FK_329937514EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('CREATE INDEX IDX_329937514EC001D1 ON score (season_id)');
        $this->addSql('CREATE UNIQUE INDEX user_difficulty ON score (user_id, season_id, hash, difficulty)');
    }
}
