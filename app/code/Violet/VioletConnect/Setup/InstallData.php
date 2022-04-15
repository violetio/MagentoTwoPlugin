<?php
namespace Violet\VioletConnect\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Violet Install Data
 *
 * @author     Rhen Zabel <rhen@violet.io>
 * @copyright  2017 Violet.io, Inc.
 * @since      1.0.1
 *
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $rows = [
            [
                'api_user_created' => false,
                'configuration_state' => 1
            ]
        ];

        foreach ($rows as $row) {
            $setup->getConnection()
            ->insertForce($setup->getTable('violetconnect'), $row);
        }
    }
}
