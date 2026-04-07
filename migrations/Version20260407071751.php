<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407071751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product ADD name VARCHAR(255) NOT NULL, ADD description LONGTEXT NOT NULL, ADD status VARCHAR(255) NOT NULL, ADD price NUMERIC(10, 2) NOT NULL, ADD created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADBF396750 FOREIGN KEY (id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE product_image ADD url VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F03BF396750 FOREIGN KEY (id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADBF396750');
        $this->addSql('ALTER TABLE product DROP name, DROP description, DROP status, DROP price, DROP created_at');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F03BF396750');
        $this->addSql('ALTER TABLE product_image DROP url');
    }
}
