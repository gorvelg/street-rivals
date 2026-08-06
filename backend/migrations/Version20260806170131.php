<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260806170131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car ADD rating INT DEFAULT 1000 NOT NULL, ADD wins INT DEFAULT 0 NOT NULL, ADD losses INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE duel ADD attacker_rating_before INT DEFAULT NULL, ADD attacker_rating_after INT DEFAULT NULL, ADD attacker_rating_delta INT DEFAULT NULL, ADD defender_rating_before INT DEFAULT NULL, ADD defender_rating_after INT DEFAULT NULL, ADD defender_rating_delta INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car DROP rating, DROP wins, DROP losses');
        $this->addSql('ALTER TABLE duel DROP attacker_rating_before, DROP attacker_rating_after, DROP attacker_rating_delta, DROP defender_rating_before, DROP defender_rating_after, DROP defender_rating_delta');
    }
}
