<?php
namespace Violet\VioletConnect\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Violet Install Schema
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 *
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $tableName = $installer->getTable('violetconnect');

        if ($installer->getConnection()->isTableExists($tableName) != true) {
            $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ]
            )
                ->addColumn(
                    'api_user_created',
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => false]
                )
                    ->addColumn(
                        'configuration_state',
                        Table::TYPE_SMALLINT,
                        null,
                        ['nullable' => false, 'default' => 1]
                    )
                        ->addColumn(
                            'token',
                            Table::TYPE_TEXT,
                            null,
                            ['nullable' => true]
                        )
                            ->addColumn(
                                'merchant_id',
                                Table::TYPE_BIGINT,
                                null,
                                ['nullable' => true]
                            )
                                ->addColumn(
                                    'last_catalog_sync_date',
                                    Table::TYPE_DATETIME,
                                    null,
                                    ['nullable' => true]
                                )
                                    ->setComment('Violet Table')
                                    ->setOption('type', 'InnoDB')
                                    ->setOption('charset', 'utf8');
                                    $installer->getConnection()->createTable($table);
                                    $indexName = $installer->getIdxName($tableName, ['id']);
                                    $installer->getConnection()->addIndex($tableName, $indexName, ['id']);
        }

                                $installer->endSetup();
    }
}
