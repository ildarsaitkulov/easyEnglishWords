<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210311200712 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE "tg_chat" (id INT NOT NULL, initiator_id INT NOT NULL, type VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, removed BOOLEAN DEFAULT NULL, chat_id_updated BOOLEAN DEFAULT NULL, remove_reason VARCHAR(500) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6E2061087DB3B714 ON "tg_chat" (initiator_id)');
        $this->addSql('CREATE TABLE "tg_user" (id INT NOT NULL, user_id INT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, language_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_862E45EBA76ED395 ON "tg_user" (user_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE "tg_chat" ADD CONSTRAINT FK_6E2061087DB3B714 FOREIGN KEY (initiator_id) REFERENCES "tg_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "tg_user" ADD CONSTRAINT FK_862E45EBA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE "tg_chat" DROP CONSTRAINT FK_6E2061087DB3B714');
        $this->addSql('ALTER TABLE "tg_user" DROP CONSTRAINT FK_862E45EBA76ED395');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE "tg_chat"');
        $this->addSql('DROP TABLE "tg_user"');
        $this->addSql('DROP TABLE "user"');
    }
}
