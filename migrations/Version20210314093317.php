<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210314093317 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE SEQUENCE meaning_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE meaning (id INT NOT NULL, word_id INT NOT NULL, external_id INT NOT NULL, text VARCHAR(255) NOT NULL, sound_url VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, difficulty_level INT DEFAULT NULL, part_of_speech_code VARCHAR(255) DEFAULT NULL, prefix VARCHAR(255) DEFAULT NULL, transcription VARCHAR(255) DEFAULT NULL, properties jsonb DEFAULT NULL, mnemonics VARCHAR(255) DEFAULT NULL, translation jsonb NOT NULL, images jsonb DEFAULT NULL, definition jsonb DEFAULT NULL, examples jsonb DEFAULT NULL, meanings_with_similar_translation jsonb DEFAULT NULL, alternative_translations jsonb DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3F31F002E357438D ON meaning (word_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_meaning_external_id ON meaning (external_id)');
        $this->addSql('ALTER TABLE meaning ADD CONSTRAINT FK_3F31F002E357438D FOREIGN KEY (word_id) REFERENCES english_word (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE word_in_learn_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE word_in_learn (id INT NOT NULL, meaning_id INT NOT NULL, word_set_id INT NOT NULL, score INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_64553F3E357438D ON word_in_learn (meaning_id)');
        $this->addSql('CREATE INDEX IDX_64553F36222D996 ON word_in_learn (word_set_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WORDSET_WORD ON word_in_learn (word_set_id, meaning_id)');
        $this->addSql('ALTER TABLE word_in_learn ADD CONSTRAINT FK_64553F3E357438D FOREIGN KEY (meaning_id) REFERENCES meaning (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE word_in_learn ADD CONSTRAINT FK_64553F36222D996 FOREIGN KEY (word_set_id) REFERENCES word_set (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE word_in_learn_id_seq CASCADE');
        $this->addSql('DROP TABLE word_in_learn');

        $this->addSql('DROP SEQUENCE meaning_id_seq CASCADE');
        $this->addSql('DROP TABLE meaning');
    }
}
