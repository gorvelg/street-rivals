<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807142058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card_narrative_phrase (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(32) NOT NULL, context VARCHAR(32) DEFAULT \'any\' NOT NULL, text LONGTEXT NOT NULL, weight INT DEFAULT 1 NOT NULL, is_enabled TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, card_id INT NOT NULL, INDEX IDX_412B2C0A4ACC9A20 (card_id), INDEX idx_card_narrative_lookup (card_id, type, context, is_enabled), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE card_narrative_phrase ADD CONSTRAINT FK_412B2C0A4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card_narrative_phrase DROP FOREIGN KEY FK_412B2C0A4ACC9A20');
        $this->addSql('DROP TABLE card_narrative_phrase');
    }
}
