<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406033334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop ADD store_name VARCHAR(255) NOT NULL, ADD store_description VARCHAR(255) DEFAULT NULL, ADD logo_url VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(255) NOT NULL, ADD rating NUMERIC(10, 0) DEFAULT NULL, ADD rating_count INT DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD seller_id INT NOT NULL');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA28DE820D9 FOREIGN KEY (seller_id) REFERENCES seller_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shop DROP FOREIGN KEY FK_AC6A4CA28DE820D9');
    }
}
