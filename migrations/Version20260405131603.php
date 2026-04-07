<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405131603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_profile CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE seller_profile ADD CONSTRAINT FK_5A59131EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A59131EA76ED395 ON seller_profile (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seller_profile DROP FOREIGN KEY FK_5A59131EA76ED395');
        $this->addSql('DROP INDEX UNIQ_5A59131EA76ED395 ON seller_profile');
        $this->addSql('ALTER TABLE seller_profile CHANGE user_id user_id BIGINT NOT NULL');
    }
}
