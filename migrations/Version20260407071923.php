<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407071923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY `FK_D34A04ADBF396750`');
        $this->addSql('ALTER TABLE product ADD shop_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD4D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD4D16C4DD ON product (shop_id)');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY `FK_64617F03BF396750`');
        $this->addSql('ALTER TABLE product_image ADD product_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_64617F034584665A ON product_image (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD4D16C4DD');
        $this->addSql('DROP INDEX IDX_D34A04AD4D16C4DD ON product');
        $this->addSql('ALTER TABLE product DROP shop_id');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT `FK_D34A04ADBF396750` FOREIGN KEY (id) REFERENCES shop (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('DROP INDEX IDX_64617F034584665A ON product_image');
        $this->addSql('ALTER TABLE product_image DROP product_id');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT `FK_64617F03BF396750` FOREIGN KEY (id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
