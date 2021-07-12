<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210711195404 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE wordset_collection_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE wordset_collection (id INT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE wordset_collection_word_set (wordset_collection_id INT NOT NULL, word_set_id INT NOT NULL, PRIMARY KEY(wordset_collection_id, word_set_id))');
        $this->addSql('CREATE INDEX IDX_E3DD86EE64181406 ON wordset_collection_word_set (wordset_collection_id)');
        $this->addSql('CREATE INDEX IDX_E3DD86EE6222D996 ON wordset_collection_word_set (word_set_id)');
        $this->addSql('ALTER TABLE wordset_collection_word_set ADD CONSTRAINT FK_E3DD86EE64181406 FOREIGN KEY (wordset_collection_id) REFERENCES wordset_collection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE wordset_collection_word_set ADD CONSTRAINT FK_E3DD86EE6222D996 FOREIGN KEY (word_set_id) REFERENCES word_set (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        $this->addSql('ALTER INDEX idx_64553f3e357438d RENAME TO IDX_64553F320A7F0E6');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wordset_collection_word_set DROP CONSTRAINT FK_E3DD86EE64181406');
        $this->addSql('DROP SEQUENCE wordset_collection_id_seq CASCADE');
        $this->addSql('DROP TABLE wordset_collection');
        $this->addSql('DROP TABLE wordset_collection_word_set');
        $this->addSql('ALTER INDEX idx_64553f320a7f0e6 RENAME TO idx_64553f3e357438d');
    }
}
