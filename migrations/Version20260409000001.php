<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order, order_item, and payment tables for Stripe/PayPal checkout';
    }

    public function up(Schema $schema): void
    {
        // ── order ──────────────────────────────────────────────────────────
        $this->addSql('
            CREATE TABLE `order` (
                id              INT AUTO_INCREMENT NOT NULL,
                user_id         INT          DEFAULT NULL,
                guest_token     VARCHAR(64)  DEFAULT NULL,
                order_number    VARCHAR(20)  NOT NULL,
                status          VARCHAR(20)  NOT NULL,
                subtotal        DECIMAL(10,2) NOT NULL,
                total           DECIMAL(10,2) NOT NULL,
                shipping_address JSON         NOT NULL,
                payment_provider VARCHAR(255) DEFAULT NULL,
                created_at      DATETIME     NOT NULL,
                updated_at      DATETIME     NOT NULL,
                UNIQUE INDEX UNIQ_F5299398977D3741 (order_number),
                INDEX IDX_F5299398A76ED395 (user_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            ALTER TABLE `order`
                ADD CONSTRAINT FK_F5299398A76ED395
                FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
        ');

        // ── order_item ─────────────────────────────────────────────────────
        $this->addSql('
            CREATE TABLE order_item (
                id           INT AUTO_INCREMENT NOT NULL,
                order_id     INT          NOT NULL,
                product_id   INT          DEFAULT NULL,
                product_name VARCHAR(255) NOT NULL,
                unit_price   DECIMAL(10,2) NOT NULL,
                quantity     INT          NOT NULL,
                line_total   DECIMAL(10,2) NOT NULL,
                INDEX IDX_52EA1F098D9F6D38 (order_id),
                INDEX IDX_52EA1F094584665A (product_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            ALTER TABLE order_item
                ADD CONSTRAINT FK_52EA1F098D9F6D38
                    FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_52EA1F094584665A
                    FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL
        ');

        // ── payment ────────────────────────────────────────────────────────
        $this->addSql('
            CREATE TABLE payment (
                id                       INT AUTO_INCREMENT NOT NULL,
                order_id                 INT          NOT NULL,
                provider                 VARCHAR(20)  NOT NULL,
                provider_transaction_id  VARCHAR(255) DEFAULT NULL,
                provider_client_secret   LONGTEXT     DEFAULT NULL,
                status                   VARCHAR(20)  NOT NULL,
                amount                   DECIMAL(10,2) NOT NULL,
                currency                 VARCHAR(3)   NOT NULL,
                raw_payload              JSON         DEFAULT NULL,
                created_at               DATETIME     NOT NULL,
                updated_at               DATETIME     NOT NULL,
                UNIQUE INDEX UNIQ_6D28840D8D9F6D38 (order_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        ');

        $this->addSql('
            ALTER TABLE payment
                ADD CONSTRAINT FK_6D28840D8D9F6D38
                FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D8D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE `order`');
    }
}
