<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201122123833 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rule (id INT AUTO_INCREMENT NOT NULL, label LONGTEXT NOT NULL, type INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rule_challenge (rule_id INT NOT NULL, challenge_id INT NOT NULL, INDEX IDX_8A59A71744E0351 (rule_id), INDEX IDX_8A59A7198A21AC6 (challenge_id), PRIMARY KEY(rule_id, challenge_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rule_challenge ADD CONSTRAINT FK_8A59A71744E0351 FOREIGN KEY (rule_id) REFERENCES rule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rule_challenge ADD CONSTRAINT FK_8A59A7198A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule_challenge DROP FOREIGN KEY FK_8A59A71744E0351');
        $this->addSql('DROP TABLE rule');
        $this->addSql('DROP TABLE rule_challenge');
    }
}
