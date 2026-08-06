<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806143042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE duel (id INT AUTO_INCREMENT NOT NULL, final_gap INT NOT NULL, random_seed VARCHAR(32) NOT NULL, engine_version VARCHAR(20) NOT NULL, attacker_snapshot JSON NOT NULL, defender_snapshot JSON NOT NULL, replay_data JSON NOT NULL, created_at DATETIME NOT NULL, attacker_car_id INT NOT NULL, defender_car_id INT NOT NULL, winner_car_id INT NOT NULL, INDEX IDX_9BB4A762A946041D (attacker_car_id), INDEX IDX_9BB4A7628CBE59E8 (defender_car_id), INDEX IDX_9BB4A762EF9C7F5E (winner_car_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A762A946041D FOREIGN KEY (attacker_car_id) REFERENCES car (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A7628CBE59E8 FOREIGN KEY (defender_car_id) REFERENCES car (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE duel ADD CONSTRAINT FK_9BB4A762EF9C7F5E FOREIGN KEY (winner_car_id) REFERENCES car (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A762A946041D');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A7628CBE59E8');
        $this->addSql('ALTER TABLE duel DROP FOREIGN KEY FK_9BB4A762EF9C7F5E');
        $this->addSql('DROP TABLE duel');
    }
}
