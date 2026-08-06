<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806210657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_event (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(64) NOT NULL, payload JSON NOT NULL, occurred_at DATETIME NOT NULL, user_id INT DEFAULT NULL, car_id INT DEFAULT NULL, duel_id INT DEFAULT NULL, INDEX IDX_99D7328A76ED395 (user_id), INDEX IDX_99D7328C3C6F69F (car_id), INDEX IDX_99D732858875E (duel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_event ADD CONSTRAINT FK_99D7328A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_event ADD CONSTRAINT FK_99D7328C3C6F69F FOREIGN KEY (car_id) REFERENCES car (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE game_event ADD CONSTRAINT FK_99D732858875E FOREIGN KEY (duel_id) REFERENCES duel (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_event DROP FOREIGN KEY FK_99D7328A76ED395');
        $this->addSql('ALTER TABLE game_event DROP FOREIGN KEY FK_99D7328C3C6F69F');
        $this->addSql('ALTER TABLE game_event DROP FOREIGN KEY FK_99D732858875E');
        $this->addSql('DROP TABLE game_event');
    }
}
