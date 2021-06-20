<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210314091839 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE SEQUENCE word_set_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE word_set (id INT NOT NULL, telegram_user_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TELEGRAM_USER_ID__TITLE ON word_set (telegram_user_id, title) WHERE telegram_user_id IS NOT NULL');
        $this->addSql('CREATE INDEX IDX_1E2AE591FC28B263 ON word_set (telegram_user_id)');
        $this->addSql('ALTER TABLE word_set ADD CONSTRAINT FK_1E2AE591FC28B263 FOREIGN KEY (telegram_user_id) REFERENCES "tg_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE word_set');
        $this->addSql('DROP SEQUENCE word_set_id_seq CASCADE');
    }
}
