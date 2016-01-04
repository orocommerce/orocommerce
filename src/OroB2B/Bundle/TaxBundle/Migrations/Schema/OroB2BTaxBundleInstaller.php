<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BTaxBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BTaxAccTaxCodeAccTable($schema);
        $this->createOroB2BTaxAccountTaxCodeTable($schema);
        $this->createOroB2BTaxProdTaxCodeProdTable($schema);
        $this->createOroB2BTaxProductTaxCodeTable($schema);
        $this->createOroB2BTaxTaxTable($schema);
        $this->createOroB2BTaxRuleTable($schema);
        $this->createOroB2BTaxJurisdictionTable($schema);
        $this->createOroB2BTaxZipCodeTable($schema);
        $this->createOroB2BTaxValueTable($schema);
        $this->createOroB2BTaxApplyToTaxValueTable($schema);
        $this->createOroB2BTaxItemValueTable($schema);
        $this->createOroB2BTaxAppToTaxIValueTable($schema);
        $this->createOroB2BTaxApplyTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BTaxAccTaxCodeAccForeignKeys($schema);
        $this->addOroB2BTaxProdTaxCodeProdForeignKeys($schema);
        $this->addOroB2BTaxRuleForeignKeys($schema);
        $this->addOroB2BTaxJurisdictionForeignKeys($schema);
        $this->addOroB2BTaxZipCodeForeignKeys($schema);
        $this->addOroB2BTaxApplyToTaxValueForeignKeys($schema);
        $this->addOroB2BTaxAppToTaxIValueForeignKeys($schema);
        $this->addOroB2BTaxApplyForeignKeys($schema);
    }

    /**
     * Create orob2b_tax_acc_tax_code_acc table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxAccTaxCodeAccTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_acc_tax_code_acc');
        $table->addColumn('account_tax_code_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->setPrimaryKey(['account_tax_code_id', 'account_id']);
        $table->addUniqueIndex(['account_id'], 'UNIQ_53167F2A9B6B5FBA');
    }

    /**
     * Create orob2b_tax_account_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxAccountTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_account_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E98BB26B77153098');
    }

    /**
     * Create orob2b_tax_prod_tax_code_prod table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxProdTaxCodeProdTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_prod_tax_code_prod');
        $table->addColumn('product_tax_code_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['product_tax_code_id', 'product_id']);
        $table->addUniqueIndex(['product_id'], 'UNIQ_3CF9D1FA4584665A');
    }

    /**
     * Create orob2b_tax_product_tax_code table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxProductTaxCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_product_tax_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_5AF53A4A77153098');
    }

    /**
     * Create orob2b_tax table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxTaxTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('rate', 'percent', ['comment' => '(DC2Type:percent)']);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E132B24377153098');
    }

    /**
     * Create orob2b_tax_rule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_tax_code_id', 'integer', ['notnull' => false]);
        $table->addColumn('tax_jurisdiction_id', 'integer', ['notnull' => false]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_tax_jurisdiction table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxJurisdictionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_jurisdiction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_2CBEF9AE77153098');
    }

    /**
     * Create orob2b_tax_zip_code table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxZipCodeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_zip_code');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_jurisdiction_id', 'integer', []);
        $table->addColumn('zip_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('zip_range_start', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('zip_range_end', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_tax_value table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('total_including_tax', 'float', []);
        $table->addColumn('total_excluding_tax', 'float', []);
        $table->addColumn('shipping_including_tax', 'float', []);
        $table->addColumn('shipping_excluding_tax', 'float', []);
        $table->addColumn('entity_id', 'integer', []);
        $table->addColumn('address', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_tax_apply_to_tax_value table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxApplyToTaxValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_apply_to_tax_value');
        $table->addColumn('tax_value_id', 'integer', []);
        $table->addColumn('tax_apply_id', 'integer', []);
        $table->addUniqueIndex(['tax_apply_id'], 'UNIQ_d1456438ad486a60');
        $table->setPrimaryKey(['tax_value_id', 'tax_apply_id']);
    }

    /**
     * Create orob2b_tax_item_value table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxItemValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_item_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('address', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('unit_price_including_tax', 'float', []);
        $table->addColumn('unit_price_excluding_tax', 'float', []);
        $table->addColumn('unit_price_tax_amount', 'float', []);
        $table->addColumn('unit_price_adjustment', 'float', []);
        $table->addColumn('row_total_including_tax', 'float', []);
        $table->addColumn('row_total_excluding_tax', 'float', []);
        $table->addColumn('row_total_tax_amount', 'float', []);
        $table->addColumn('row_total_adjustment', 'float', []);
        $table->addColumn('entity_id', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_tax_app_to_tax_i_value table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxAppToTaxIValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_app_to_tax_i_value');
        $table->addColumn('tax_item_value_id', 'integer', []);
        $table->addColumn('tax_apply_id', 'integer', []);
        $table->addUniqueIndex(['tax_apply_id'], 'UNIQ_99889937ad486a60');
        $table->setPrimaryKey(['tax_item_value_id', 'tax_apply_id']);
    }

    /**
     * Create orob2b_tax_apply table
     *
     * @param Schema $schema
     */
    protected function createOroB2BTaxApplyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_tax_apply');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('tax_id', 'integer', ['notnull' => false]);
        $table->addColumn('rate', 'percent', ['comment' => '(DC2Type:percent)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('tax_amount', 'float', []);
        $table->addColumn('taxable_amount', 'float', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_tax_acc_tax_code_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxAccTaxCodeAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_acc_tax_code_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_prod_tax_code_prod foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxProdTaxCodeProdForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_prod_tax_code_prod');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax'),
            ['tax_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_account_tax_code'),
            ['account_tax_code_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_product_tax_code'),
            ['product_tax_code_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_jurisdiction'),
            ['tax_jurisdiction_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_tax_jurisdiction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxJurisdictionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_jurisdiction');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_zip_code foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxZipCodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_zip_code');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_jurisdiction'),
            ['tax_jurisdiction_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_tax_apply_to_tax_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxApplyToTaxValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_apply_to_tax_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_value'),
            ['tax_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_apply'),
            ['tax_apply_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_tax_app_to_tax_i_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxAppToTaxIValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_app_to_tax_i_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_item_value'),
            ['tax_item_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax_apply'),
            ['tax_apply_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_tax_apply foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BTaxApplyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_tax_apply');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_tax'),
            ['tax_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
