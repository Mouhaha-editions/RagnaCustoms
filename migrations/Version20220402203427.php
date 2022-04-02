<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220402203427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score_history DROP difficulty, DROP hash, DROP notes_hit, DROP notes_missed, DROP notes_not_processed, DROP percentage, DROP percentage2, DROP hit_speed, DROP combos, DROP song');
        $this->addSql('ALTER TABLE score DROP difficulty, DROP hash, DROP notes_hit, DROP notes_missed, DROP notes_not_processed, DROP percentage, DROP percentage2, DROP hit_speed, DROP combos, DROP song');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score_history ADD difficulty VARCHAR(255) DEFAULT NULL, ADD hash VARCHAR(255) DEFAULT NULL, ADD notes_hit INT DEFAULT NULL, ADD notes_missed INT DEFAULT NULL, ADD notes_not_processed INT DEFAULT NULL, ADD percentage NUMERIC(20, 6) DEFAULT NULL, ADD percentage2 NUMERIC(20, 6) DEFAULT NULL, ADD hit_speed NUMERIC(10, 2) DEFAULT NULL, ADD combos INT DEFAULT NULL, ADD song VARCHAR(255) DEFAULT NULL');
    }
}
