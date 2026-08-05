<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805120843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE card_choice (id INT AUTO_INCREMENT NOT NULL, level SMALLINT NOT NULL, created_at DATETIME NOT NULL, selected_at DATETIME DEFAULT NULL, car_id INT NOT NULL, first_card_id INT NOT NULL, second_card_id INT NOT NULL, selected_card_id INT DEFAULT NULL, INDEX IDX_B39409E6C3C6F69F (car_id), INDEX IDX_B39409E65940F6DC (first_card_id), INDEX IDX_B39409E65D8E1A4D (second_card_id), INDEX IDX_B39409E6A288E4BE (selected_card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE card_choice ADD CONSTRAINT FK_B39409E6C3C6F69F FOREIGN KEY (car_id) REFERENCES car (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE card_choice ADD CONSTRAINT FK_B39409E65940F6DC FOREIGN KEY (first_card_id) REFERENCES card (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE card_choice ADD CONSTRAINT FK_B39409E65D8E1A4D FOREIGN KEY (second_card_id) REFERENCES card (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE card_choice ADD CONSTRAINT FK_B39409E6A288E4BE FOREIGN KEY (selected_card_id) REFERENCES card (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card_choice DROP FOREIGN KEY FK_B39409E6C3C6F69F');
        $this->addSql('ALTER TABLE card_choice DROP FOREIGN KEY FK_B39409E65940F6DC');
        $this->addSql('ALTER TABLE card_choice DROP FOREIGN KEY FK_B39409E65D8E1A4D');
        $this->addSql('ALTER TABLE card_choice DROP FOREIGN KEY FK_B39409E6A288E4BE');
        $this->addSql('DROP TABLE card_choice');
    }
}
