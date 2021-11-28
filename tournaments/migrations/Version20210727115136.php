<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210727115136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule ADD position INT DEFAULT NULL');
        $this->addSql('INSERT INTO ext_translations (SELECT NULL,"fr","App\\\Entity\\\Challenge","description",id,description FROM challenge)');
        $this->addSql('INSERT INTO ext_translations (SELECT NULL,"en","App\\\Entity\\\Chalenge","description",id,description FROM challenge)');
        $this->addSql('INSERT INTO ext_translations (SELECT NULL,"fr","App\\\Entity\\\Rule","label",id,label FROM rule)');
        $this->addSql('INSERT INTO ext_translations (SELECT NULL,"en","App\\\Entity\\\Rule","label",id,label FROM rule)');


    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rule DROP position');
    }
}
