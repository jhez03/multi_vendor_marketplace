<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature: Product Categories
 *
 * 1. Rebuilds the `product_category` table with all required columns
 *    (was an empty stub with only `id`).
 * 2. Adds a nullable `category_id` foreign key to `product` so existing
 *    products are not broken (they simply have no category yet).
 * 3. Seeds eight sensible default categories so the UI is not empty
 *    on first deploy.
 *
 * Rollback: removes the FK, drops new columns from product_category,
 * and deletes the seeded rows.
 */
final class Version20260412000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Flesh out product_category table and add category FK to product';
    }

    public function up(Schema $schema): void
    {
        // ── 1. Add new columns to product_category ───────────────────────────
        // The table already exists (created in the initial migration) with only
        // `id`.  We ADD columns rather than recreating so we don't break any
        // existing FK constraints that may reference this table.
        $this->addSql(<<<'SQL'
                ALTER TABLE product_category
                    ADD name        VARCHAR(100)  NOT NULL        AFTER id,
                    ADD slug        VARCHAR(120)  NOT NULL        AFTER name,
                    ADD description VARCHAR(255)  DEFAULT NULL    AFTER slug,
                    ADD icon        VARCHAR(10)   DEFAULT NULL    AFTER description,
                    ADD sort_order  INT           NOT NULL DEFAULT 0 AFTER icon,
                    ADD is_active   TINYINT(1)    NOT NULL DEFAULT 1 AFTER sort_order,
                    ADD created_at  DATETIME      NOT NULL        AFTER is_active,
                    ADD UNIQUE INDEX UNIQ_CAT_SLUG (slug)
            SQL);

        // ── 2. Add category FK to product ────────────────────────────────────
        $this->addSql(<<<'SQL'
                ALTER TABLE product
                    ADD category_id INT DEFAULT NULL
            SQL);

        $this->addSql(<<<'SQL'
                ALTER TABLE product
                    ADD CONSTRAINT FK_D34A04AD12469DE2
                    FOREIGN KEY (category_id)
                    REFERENCES product_category (id)
                    ON DELETE SET NULL
            SQL);

        $this->addSql(<<<'SQL'
                CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)
            SQL);

        // ── 3. Seed default categories ────────────────────────────────────────
        // Using parameterised VALUES so special chars in names are safe.
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $seeds = [
            ['Electronics',   'electronics',    'Gadgets, devices & accessories',  '💻', 1],
            ['Fashion',       'fashion',         'Clothing, shoes & accessories',   '👕', 2],
            ['Home & Living', 'home-living',     'Furniture, décor & kitchenware',  '🏠', 3],
            ['Handmade',      'handmade',        'Unique crafted & artisan goods',  '🎨', 4],
            ['Health & Beauty','health-beauty',  'Skincare, wellness & fitness',    '💄', 5],
            ['Sports',        'sports',          'Gear, apparel & equipment',       '⚽', 6],
            ['Books & Media', 'books-media',     'Books, music, games & films',     '📚', 7],
            ['Food & Drinks', 'food-drinks',     'Local produce & specialty foods', '🍜', 8],
        ];

        foreach ($seeds as [$name, $slug, $desc, $icon, $sort]) {
            $this->addSql(
                'INSERT INTO product_category (name, slug, description, icon, sort_order, is_active, created_at)
                 VALUES (:name, :slug, :desc, :icon, :sort, 1, :now)',
                [
                    'name' => $name,
                    'slug' => $slug,
                    'desc' => $desc,
                    'icon' => $icon,
                    'sort' => $sort,
                    'now'  => $now,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Remove FK from product first
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('DROP INDEX IDX_D34A04AD12469DE2 ON product');
        $this->addSql('ALTER TABLE product DROP category_id');

        // Remove seeded rows and new columns from product_category
        $this->addSql('DELETE FROM product_category');
        $this->addSql('DROP INDEX UNIQ_CAT_SLUG ON product_category');
        $this->addSql('ALTER TABLE product_category
            DROP name,
            DROP slug,
            DROP description,
            DROP icon,
            DROP sort_order,
            DROP is_active,
            DROP created_at
        ');
    }
}
