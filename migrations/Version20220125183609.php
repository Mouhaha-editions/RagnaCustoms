<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220125183609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE song_feedback DROP FOREIGN KEY FK_79F51210CC3E94CB');
        $this->addSql('ALTER TABLE vote ADD feedback LONGTEXT DEFAULT NULL, ADD hash VARCHAR(255) DEFAULT NULL, ADD is_anonymous TINYINT(1) DEFAULT NULL, ADD is_moderated TINYINT(1) DEFAULT NULL, ADD is_public TINYINT(1) DEFAULT NULL, CHANGE readability readability DOUBLE PRECISION DEFAULT NULL, CHANGE level_quality level_quality DOUBLE PRECISION DEFAULT NULL');

        $this->addSql('INSERT INTO vote (user_id, song_id, created_at, updated_at)(SELECT sf.user_id,sf.song_id, sf.created_at, sf.updated_at FROM  song_feedback sf LEFT JOIN vote v ON sf.song_id = v.song_id AND sf.user_id =  v.user_id WHERE v.id IS NULL);');
        $this->addSql('UPDATE vote SET 
feedback = (SELECT feedback FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id)
,is_moderated = (SELECT is_moderated FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id)
,is_anonymous = (SELECT is_anonymous FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id)
,is_public = (SELECT is_public FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id)
,hash = (SELECT hash FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id)
WHERE (SELECT id FROM song_feedback WHERE user_id = vote.user_id AND vote.song_id = song_id) IS NOT NULL;');

        $this->addSql('DROP TABLE song_feedback');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_feedback (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, feedback_parent_id INT DEFAULT NULL, song_id INT DEFAULT NULL, is_public TINYINT(1) NOT NULL, is_anonymous TINYINT(1) NOT NULL, feedback LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_moderated TINYINT(1) NOT NULL, hash VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, difficulty VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_79F51210CC3E94CB (feedback_parent_id), INDEX IDX_79F51210A76ED395 (user_id), INDEX IDX_79F51210A0BDB2F3 (song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210CC3E94CB FOREIGN KEY (feedback_parent_id) REFERENCES song_feedback (id)');
        $this->addSql('ALTER TABLE song_feedback ADD CONSTRAINT FK_79F51210A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE vote DROP feedback, DROP hash, DROP is_anonymous, DROP is_moderated, DROP is_public, CHANGE level_quality level_quality DOUBLE PRECISION NOT NULL, CHANGE readability readability DOUBLE PRECISION NOT NULL');
    }
}
