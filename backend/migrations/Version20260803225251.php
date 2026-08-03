<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803225251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car_card (id INT AUTO_INCREMENT NOT NULL, is_equipped TINYINT NOT NULL, acquired_level INT NOT NULL, acquired_at DATETIME NOT NULL, car_id INT NOT NULL, card_id INT NOT NULL, INDEX IDX_DD7E8C7FC3C6F69F (car_id), INDEX IDX_DD7E8C7F4ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE car_card ADD CONSTRAINT FK_DD7E8C7FC3C6F69F FOREIGN KEY (car_id) REFERENCES car (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE car_card ADD CONSTRAINT FK_DD7E8C7F4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car_card DROP FOREIGN KEY FK_DD7E8C7FC3C6F69F');
        $this->addSql('ALTER TABLE car_card DROP FOREIGN KEY FK_DD7E8C7F4ACC9A20');
        $this->addSql('DROP TABLE car_card');
    }
}
