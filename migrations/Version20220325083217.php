<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325083217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score ADD extra LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE score_history ADD combo_blue INT DEFAULT NULL, ADD combo_yellow INT DEFAULT NULL, ADD hit INT DEFAULT NULL, ADD hit_delta_average INT DEFAULT NULL, ADD hit_percentage INT DEFAULT NULL, ADD missed INT DEFAULT NULL, ADD percentage_of_perfects INT DEFAULT NULL, ADD extra LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE score DROP extra');
        $this->addSql('ALTER TABLE score_history DROP combo_blue, DROP combo_yellow, DROP hit, DROP hit_delta_average, DROP hit_percentage, DROP missed, DROP percentage_of_perfects, DROP extra');
    }
}
