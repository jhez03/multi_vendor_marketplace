<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405130955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE buyer_profile ADD full_name VARCHAR(255) NOT NULL, ADD avatar_url VARCHAR(255) DEFAULT NULL, ADD birth_date VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE buyer_profile ADD CONSTRAINT FK_21FB0379A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_21FB0379A76ED395 ON buyer_profile (user_id)');
        $this->addSql('ALTER TABLE seller_profile ADD user_id BIGINT NOT NULL, ADD display_name VARCHAR(255) NOT NULL, ADD is_verified TINYINT NOT NULL, ADD created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE buyer_profile DROP FOREIGN KEY FK_21FB0379A76ED395');
        $this->addSql('DROP INDEX UNIQ_21FB0379A76ED395 ON buyer_profile');
        $this->addSql('ALTER TABLE buyer_profile DROP full_name, DROP avatar_url, DROP birth_date, DROP created_at, DROP user_id');
        $this->addSql('ALTER TABLE seller_profile DROP user_id, DROP display_name, DROP is_verified, DROP created_at');
    }
}
